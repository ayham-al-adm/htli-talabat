<script>
import { Link,Head, useForm } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import Pagination from "@/Components/Pagination.vue";
import dropZone from "@/Components/widgets/dropZone.vue";
import search from "@/Components/widgets/search.vue";
import searchbar from "@/Components/widgets/searchbar.vue";
import imageUpload from "@/Components/widgets/imageUpload.vue";
import Swal from "sweetalert2";
import { ref, watch } from "vue";
import axios from "axios";
import { debounce } from 'lodash';
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { i18nT } from '@/i18n';

export default {
    data() {
    return {
        rightOffcanvas: false,
    };
  },
    components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
        Multiselect,
        flatPickr,
        Link,
        dropZone,
        imageUpload,
        search,
        searchbar,
    },
    setup(props) {
        const searchTerm = ref("");
        const selectedRole = ref(null);
        const form = useForm({
            name: "",
            description: "",
        });
        const filter = useForm({
            all: "",
            locked: "",
        });
        const roles = ref([]); // Spread the roles to make them reactive
        const paginator = ref({}); // Spread the roles to make them reactive
        const modalShow = ref(false);
        const modalForm = ref(false);
        const modalFilter = ref(false);
        const deleteItemId = ref(null);

        const successMessage = ref(props.successMessage || '');
        const alertMessage = ref(props.alertMessage || '');

        const dismissMessage = () => {
            successMessage.value = "";
            alertMessage.value = "";
        };

        const createRole = () => {
            form.reset();
            selectedRole.value = null;
            modalForm.value = true;
        };

        const filterRole = () => {
            modalFilter.value = true;
        };

        const editRole = (role) => {
            selectedRole.value = role;
            form.name = role.name;
            form.description = role.description;
            form.clearErrors();
            modalForm.value = true;
        };

        const cancelEdit = () => {
            form.reset();
            modalForm.value = false;
            selectedRole.value = null;
        };

        const clearFilter = () => {
            filter.reset();
            fetchRoles();
            modalFilter.value = false;
        };

        const validateForm = () => {
            const { name, description } = form;
            const errors = {};

            const validationRules = {
                name: {
                    test: () => !name,
                    message: i18nT('role_name_is_required')
                },
                description: {
                    test: () => !description,
                    message: i18nT('role_description_is_required')
                },
            };

            Object.keys(validationRules).forEach(fieldName => {
                if (validationRules[fieldName].test()) {
                    errors[fieldName] = validationRules[fieldName].message;
                }
            });

            return errors;
        };

        const handleSubmit = async () => {
            const errors = validateForm();
            if (Object.keys(errors).length === 0) {
                try {
                    let response;
                    if (selectedRole.value) {
                        response = await axios.put(`/roles/update/${selectedRole.value.id}`, form.data());
                    } else {
                        response = await axios.post("/roles", form.data());
                    }

                    if (response.status === 200 || response.status === 201) {
                        successMessage.value = response.data.successMessage;
                        const responseData = response.data.roles;
                        if (selectedRole.value) {
                            const index = roles.value.findIndex(role => role.id === selectedRole.value.id);
                            if (index !== -1) {
                                roles.value[index] = responseData;
                            }
                        } else {
                            roles.value.push(responseData);
                        }
                        modalForm.value = false;
                        form.reset();
                    } else {
                        console.error('Unexpected response status:', response.status);
                    }

                } catch (error) {
                    console.error("Error handling form submission:", error);
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        form.errors = errors;
                    } else {
                        console.error("An unexpected error occurred:", error);
                    }
                }
            } else {
                form.errors = errors;
            }
        };

        const hideError = (field) => {
            form.errors[field] = '';
        };

        const closeModal = () => {
            modalShow.value = false;
        };
        const deleteRole = async (roleId) => {
            try {
                await axios.delete(`/roles/delete/${roleId}`);
                const index = roles.value.findIndex(role => role.id === roleId);
                if (index !== -1) {
                    roles.value.splice(index, 1);
                }
                modalShow.value = false;
                Swal.fire('Success', 'Role deleted successfully', 'success');
            } catch (error) {
                console.error("Error deleting role:", error);
                Swal.fire('Error', 'Failed to delete role', 'error');
            }
        };

        const deleteModal = async (itemId) => {
            Swal.fire({
                title: i18nT('are_you_sure'),
                text: i18nT('you_wont_be_able_to_revert_this'),
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#34c38f",
                cancelButtonColor: "#f46a6a",
                confirmButtonText: i18nT('yes_delete_it'),
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        await deleteRole(itemId);
                    } catch (error) {
                        console.error("Error deleting role:", error);
                        Swal.fire("Error", "Failed to delete the role", "error");
                    }
                }
            });
        };

        watch(searchTerm,debounce(function(value){
            fetchRoles(searchTerm.value);

        },300));

        const fetchRoles = async (page = 1) => {
            try {
                const params = filter.data();
                params.search = searchTerm.value;
                params.page = page;
                const response = await axios.get(`/roles1/list`, { params });
                roles.value = response.data.roles;
                paginator.value = response.data.paginator;
                modalFilter.value = false;
            } catch (error) {
                console.error("Error fetching roles:", error);
            }
        };

        const handlePageChanged = async (page) => {
            fetchRoles(page);
        };

        return {
            form,
            roles,
            modalShow,
            modalForm,
            deleteItemId,
            selectedRole,
            successMessage,
            alertMessage,
            createRole,
            filterRole,
            editRole,
            cancelEdit,
            handleSubmit,
            deleteModal,
            closeModal,
            deleteRole,
            dismissMessage,
            hideError,
            searchTerm,
            paginator,
            modalFilter,
            clearFilter,
            fetchRoles,
            filter,
            handlePageChanged

        };
    },
};
</script>

<template>
    <Layout>

        <Head :title="$t('image')" />
        <PageHeader :title="$t('image')" pageTitle="Image" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="tasksList">

                    <BCardHeader class="border-0">
                        <BRow class="g-2">
                            <BCol md="3">
                            </BCol>
                            <BCol md="auto" class="ms-auto">
                                
                                <div class="d-flex align-items-center gap-2">
                                    <searchbar></searchbar>
                                    <BButton variant="danger" @click="rightOffcanvas = true"><i
                                            class="ri-filter-2-line me-1 align-bottom"></i> {{ $t('filters') }}</BButton>

                                
                                <Link :href="`/images/create`">
                                    <BButton variant="primary" class="float-end"> <i
                                            class="ri-add-line align-bottom me-1"></i> {{ $t('add_image') }}</BButton>
                                    </Link>
                                </div>
                            </BCol>
                        </BRow>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <div class="todo-task" id="todo-task">
                        <div class="table-responsive">
                            <table class="table align-middle position-relative table-nowrap">
                                <thead class="table-active">
                                    <tr>
                                        <th scope="col">{{ $t('s_no') }}</th>
                                        <th scope="col">{{ $t('vehicle_make_name') }}</th>
                                        <th scope="col">{{ $t('vehicle_image') }}</th>
                                        <th scope="col">{{ $t('status') }}</th>
                                        <th scope="col">{{ $t('action') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td> Audi</td>
                                        <td> <img src="@assets/images/users/avatar-1.jpg" alt="" class="avatar-xs rounded-circle me-2"></td>
                                        <td><BBadge variant="success"
                                                class="text-uppercase">{{ $t('active') }}</BBadge>
                                        </td>
                                        <td>
                                            <div class="hstack gap-2">
                                                <Link href="/vehicle-make/update"
                                                    class="btn btn-soft-info btn-sm m-2">
                                                    <i class='bx bxs-edit-alt'></i>
                                                </Link>
                                                <BButton class="btn btn-soft-danger btn-sm m-2" size="sm" type="button"
                                                    id="sa-warning" @click.prevent="deleteModal(role.id)">
                                                    <i class='bx bx-trash'></i>
                                                </BButton>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td> BMW</td>
                                        <td> <img src="@assets/images/users/avatar-1.jpg" alt="" class="avatar-xs rounded-circle me-2"></td>
                                        <td><BBadge variant="danger"
                                                class="text-uppercase">{{ $t('inactive') }}</BBadge>
                                        </td>
                                        <td>
                                            <div class="hstack gap-2">
                                                <Link href="/vehicle-make/update"
                                                    class="btn btn-soft-info btn-sm m-2">
                                                    <i class='bx bxs-edit-alt'></i>
                                                </Link>
                                                <BButton class="btn btn-soft-danger btn-sm m-2" size="sm" type="button"
                                                    id="sa-warning" @click.prevent="deleteModal(role.id)">
                                                    <i class='bx bx-trash'></i>
                                                </BButton>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
        <div>
            <!-- Success Message -->
            <div v-if="successMessage" class="custom-alert alert alert-success alert-border-left fade show" role="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>{{ $t('success') }}</strong> - {{
                        successMessage }}
                    <button type="button" class="btn-close btn-close-success" @click="dismissMessage"
                        aria-label="Close Success Message"></button>
                </div>
            </div>

            <!-- Alert Message -->
            <div v-if="alertMessage" class="custom-alert alert alert-danger alert-border-left fade show" role="alert"
                id="alertMsg">
                <div class="alert-content">
                    <i class="ri-notification-off-line me-3 align-middle"></i> <strong>{{ $t('alert') }}</strong> - {{ alertMessage
                    }}
                    <button type="button" class="btn-close btn-close-danger" @click="dismissMessage"
                        aria-label="Close Alert Message"></button>
                </div>
            </div>
        </div>

        <!-- Create or Edit Form Modal -->
<!-- filter -->
        <BOffcanvas v-model="rightOffcanvas" placement="end" :title="$t('leads_filters')" header-class="bg-light"
              body-class="p-0 overflow-hidden" footer-class="border-top p-3 text-center">
              <BFrom action="" class="d-flex flex-column justify-content-end h-100">
                <div class="offcanvas-body">
                  <div class="mb-4">
                    <label for="datepicker-range"
                      class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t('process') }}</label>
                    <select class="form-control" data-choices data-choices-search-false name="choices-select-status"
                        id="choices-select-status" v-model="status">
                        <option value="All Tasks">{{ $t('all') }}</option>
                        <option value="Completed">{{ $t('completed') }}</option>
                        <option value="Inprogress">{{ $t('inprogress') }}</option>
                        <option value="Pending">{{ $t('pending') }}</option>
                        <option value="Pending">{{ $t('cancelled') }}</option>
                    </select>
                  </div>

                  <div class="mb-4">
                    <label for="datepicker-range"
                      class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t('payment') }}</label>
                        <div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="WithoutinlineRadio1" value="option1">
                            <label class="form-check-label" for="inlineRadio1">{{ $t('online') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="WithoutinlineRadio2" value="option2">
                            <label class="form-check-label" for="inlineRadio1">{{ $t('card') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="WithoutinlineRadio3" value="option3">
                            <label class="form-check-label" for="inlineRadio1">{{ $t('cash') }}</label>
                            </div>
                        </div>
                    </div>
                  <div class="mb-4">
                    <label for="datepicker-range"
                      class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t('date') }}</label>
                    <flat-pickr :placeholder="$t('select_date')" v-model="date" :config="rangeDateconfig"
                      class="form-control flatpickr-input" id="demo-datepicker"></flat-pickr>
                  </div>
                  
                  <div class="mb-4">
                    <label for="country-select"
                      class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t('country') }}</label>

                    <Multiselect class="form-control" v-model="value" :close-on-select="true" :searchable="true"
                      :create-option="true" :options="[
                        { value: '', label: 'Select country' },
                        { value: 'Argentina', label: 'Argentina' },
                        { value: 'Belgium', label: 'Belgium' },
                        { value: 'Brazil', label: 'Brazil' },
                        { value: 'Colombia', label: 'Colombia' },
                        { value: 'Denmark', label: 'Denmark' },
                        { value: 'France', label: 'France' },
                        { value: 'Germany', label: 'Germany' },
                        { value: 'Mexico', label: 'Mexico' },
                        { value: 'Russia', label: 'Russia' },
                        { value: 'Spain', label: 'Spain' },
                        { value: 'Syria', label: 'Syria' },
                        { value: 'United Kingdom', label: 'United Kingdom' },
                      ]" />
                  </div>
                  <div class="mb-4">
                    <label for="status-select"
                      class="form-label text-muted text-uppercase fw-semibold mb-3">{{ $t('status') }}</label>
                    <BRow class="g-2">
                      <BCol lg="6">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1" />
                          <label class="form-check-label" for="inlineCheckbox1">{{ $t('active') }}</label>
                        </div>
                      </BCol>
                      <BCol lg="6">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2" />
                          <label class="form-check-label" for="inlineCheckbox2">{{ $t('inactive') }}</label>
                        </div>
                      </BCol>
                      <BCol lg="6">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="inlineCheckbox3" value="option3" />
                          <label class="form-check-label" for="inlineCheckbox3">{{ $t('cash') }}</label>
                        </div>
                      </BCol>
                      <BCol lg="6">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="inlineCheckbox4" value="option4" />
                          <label class="form-check-label" for="inlineCheckbox4">{{ $t('card') }}</label>
                        </div>
                      </BCol>
                    </BRow>
                  </div>
                  <div>
                  </div>
                </div>
                <!--end offcanvas-body-->
                <div class="offcanvas-footer border-top p-3 text-center hstack gap-2">
                  <BButton variant="light" class="w-100">{{ $t('clear_filter') }}</BButton>
                  <BButton type="submit" variant="success" class="w-100">
                    {{ $t('apply') }}
                  </BButton>
                </div>
                <!--end offcanvas-footer-->
              </BFrom>
            </BOffcanvas>
            <!--end offcanvas-->
            <!-- filter end -->

        <!-- Pagination -->
        <Pagination :paginator=paginator @page-changed="handlePageChanged"/>
    </Layout>
</template>
<style>
.custom-alert {
    max-width: 600px;
    float: right;
    position: fixed;
    top: 90px;
    right: 20px;
}

.text-danger {
    padding-top: 5px;
}

</style>
