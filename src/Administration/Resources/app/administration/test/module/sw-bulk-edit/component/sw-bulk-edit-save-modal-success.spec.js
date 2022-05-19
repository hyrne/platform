import { shallowMount } from '@vue/test-utils';
import 'src/module/sw-bulk-edit/component/sw-bulk-edit-save-modal-success';

const swBulkEditState = {
    namespaced: true,
    state() {
        return {
            orderDocuments: {
                invoice: {
                    isChanged: true,
                    value: {
                        documentDate: null,
                        documentComment: null,
                    },
                },
                download: {
                    isChanged: true,
                    value: [
                        {
                            technicalName: 'invoice',
                            selected: true,
                            translated: {
                                name: 'invoice'
                            }
                        },
                    ],
                },
            },
        };
    },
    getters: {
        documentTypeConfigs: () => {
            return [
                {
                    fileType: 'pdf',
                    type: 'invoice',
                    config: {
                        documentDate: null,
                        documentComment: null,
                    },
                },
            ];
        }
    },
};

function createWrapper() {
    return shallowMount(Shopware.Component.build('sw-bulk-edit-save-modal-success'), {
        stubs: {
            'sw-label': true,
            'sw-icon': true,
            'sw-button': true,
        },
        provide: {
            repositoryFactory: {
                create: () => {
                    return {
                        search: () => Promise.resolve([]),
                    };
                },
            },
            orderDocumentApiService: {
                create: () => {
                    return Promise.resolve();
                },
                download: () => {
                    return Promise.resolve();
                },
            },
        }
    });
}

describe('sw-bulk-edit-save-modal-success', () => {
    let wrapper;

    beforeAll(() => {
        Shopware.State.registerModule('swBulkEdit', swBulkEditState);
    });

    beforeEach(() => {
        wrapper = createWrapper();
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('should be a Vue.js component', () => {
        expect(wrapper.vm).toBeTruthy();
    });

    it('should get latest documents when component created', async () => {
        wrapper.vm.getLatestDocuments = jest.fn();

        await wrapper.vm.createdComponent();

        expect(wrapper.vm.getLatestDocuments).toHaveBeenCalled();
        wrapper.vm.getLatestDocuments.mockRestore();
    });

    it('should be able to download documents', async () => {
        wrapper.vm.orderDocumentApiService.download = jest.fn(() => Promise.resolve());

        await wrapper.setData({
            latestDocuments: {
                invoice: {
                    foo: 'bar',
                }
            }
        });
        await wrapper.vm.downloadDocument('invoice');

        expect(wrapper.vm.orderDocumentApiService.download).toHaveBeenCalled();
        wrapper.vm.orderDocumentApiService.download.mockRestore();
    });

    it('should not be able to download documents', async () => {
        wrapper.vm.orderDocumentApiService.download = jest.fn(() => Promise.resolve());

        await wrapper.setData({
            latestDocuments: {}
        });
        await wrapper.vm.downloadDocument('invoice');

        expect(wrapper.vm.orderDocumentApiService.download).not.toHaveBeenCalled();
        wrapper.vm.orderDocumentApiService.download.mockRestore();
    });
});
