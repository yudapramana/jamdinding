<script setup>
import axios from "axios";
import { ref, onMounted, reactive, watch, toDisplayString } from "vue";
import { Form, Field, useResetForm } from "vee-validate";
import * as yup from "yup";
import OrgListItem from "./OrgListItem.vue";
import { debounce } from 'lodash';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';
import { useToastr } from "../../toastr.js";

const toastr = useToastr();

const orgs = ref({ 'data': [] });
const editing = ref(false);
const formValues = ref({
    id: "",
    name: "",
    email: "",
});

const form = ref(null);

const getOrgs = (page = 1) => {
    axios.get(`/api/orgs?page=${page}`, {
        params: {
            search_query: searchQuery.value
        }
    }).then((response) => {
        console.log(response);
        orgs.value = response.data;
        selectedOrgs.value = [];
        selectAll.value = false;
    });
};


const createOrgSchema = yup.object({
    name: yup.string().required(),
    email: yup.string().email().required(),
});

const editOrgSchema = yup.object({
    name: yup.string().required(),
    email: yup.string().email().required(),
});

const createOrg = (values, { resetForm, setFieldError, setErrors }) => {
    axios
        .post("/api/orgs", values)
        .then((response) => {
            orgs.value.data.unshift(response.data);
            $("#defaultModal").modal("hide");
            resetForm();
            toastr.success("Orgs created successfully!");
        })
        .catch((error) => {
            if (error.response.data.errors) {
                setErrors(error.response.data.errors);
            }
        });
};

const addOrg = () => {
    editing.value = false;
    form.value.resetForm();
    formValues.value = {
        id: "",
        name: "",
        email: "",
    };
    $("#defaultModal").modal("show");
};

const editOrg = (org) => {
    editing.value = true;
    form.value.resetForm();
    $("#defaultModal").modal("show");
    console.log("org");
    console.log(org.id);
    console.log(org.name);

    formValues.value = {
        id: org.id,
        name: org.name,
        email: org.email,
    };
};

const updateOrg = (values, { setErrors }) => {
    console.log(values);
    axios
        .put("/api/orgs/" + formValues.value.id, values)
        .then((response) => {
            const index = orgs.value.data.findIndex(
                (org) => org.id === response.data.id
            );
            orgs.value.data[index] = response.data;
            $("#defaultModal").modal("hide");
            toastr.success("Org updated successfully!");
        })
        .catch((error) => {
            if (error.response) {
                setErrors(error.response.data.errors);
            }
        });
};

const handleSubmit = (values, actions) => {
    console.log(actions);
    if (editing.value) {
        updateOrg(values, actions);
    } else {
        createOrg(values, actions);
    }
};

const searchQuery = ref(null);

const selectedOrgs = ref([]);

const toggleSelection = (org) => {
    const index = selectedOrgs.value.indexOf(org.id);
    if (index === -1) {
        selectedOrgs.value.push(org.id);
    } else {
        selectedOrgs.value.splice(index, 1);
    }
}


const orgIdBeingDeleted = ref(null);

const confirmOrgDeletion = (id) => {
    orgIdBeingDeleted.value = id;
    $("#deleteModal").modal("show");
};

const deleteOrg = () => {
    axios.delete(`/api/orgs/${orgIdBeingDeleted.value}`)
        .then(() => {
            $("#deleteModal").modal("hide");
            toastr.success('Org deleted Successfully!');
            orgs.value.data = orgs.value.data.filter(org => org.id !== orgIdBeingDeleted.value);
        })
}

const bulkDelete = () => {
    axios.delete('/api/orgs', {
        data: {
            ids: selectedOrgs.value
        }
    })
        .then(response => {
            orgs.value.data = orgs.value.data.filter(org => !selectedOrgs.value.includes(org.id));
            selectedOrgs.value = [];
            selectAll.value = false;
            toastr.success(response.data.message);
        });
}

const selectAll = ref(false);
const selectAllOrgs = () => {
    if (selectAll.value) {
        selectedOrgs.value = orgs.value.data.map(org => org.id);
    } else {
        selectedOrgs.value = [];
    }
}

watch(searchQuery, debounce(() => {
    // search();
    getOrgs();
}, 300));

onMounted(() => {
    getOrgs();
});
</script>
<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Daftar Satuan Kerja</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Satuan Kerja</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between">
                <div class="d-flex">
                    <button @click="addOrg" type="button" class="mb-2 btn btn-primary">
                        <i class="fa fa-plus-circle mr-1"></i>
                        Tambah Satker
                    </button>
                    <div v-if="selectedOrgs.length > 0">
                        <button @click="bulkDelete" type="button" class="ml-2 mb-2 btn btn-danger">
                            <i class="fa fa-trash" mr-1></i>
                            Delete Selected
                        </button>
                        <span class="ml-2">Selected {{ selectedOrgs.length }} organizations</span>
                    </div>
                </div>

                <div>
                    <input type="text" v-model="searchQuery" class="form-control" placeholder="Search...">

                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" v-model="selectAll" @change="selectAllOrgs" /></th>
                                    <th style="width: 10px">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody v-if="orgs.data.length > 0">
                                <OrgListItem v-for="(org, index) in orgs.data" :key="org.id" :org="org" :index="index"
                                    @edit-org="editOrg" @confirm-org-deletion="confirmOrgDeletion"
                                    @toggle-selection="toggleSelection" :select-all="selectAll" />
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="text-center">No results found...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <Bootstrap4Pagination :data="orgs" @pagination-change-page="getOrgs" :limit="1" :keepLength="true" />

        </div>
    </div>

    <!-- Default Create Update Modal -->
    <div class="modal fade" id="defaultModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <span v-if="editing">Edit Org</span>
                        <span v-else>Add New Org</span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <Form ref="form" @submit="handleSubmit" :validation-schema="editing ? editOrgSchema : createOrgSchema"
                    v-slot="{ errors }" :initial-values="formValues">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <Field v-model="formValues.name" type="text" name="name" id="name" class="form-control"
                                :class="{ 'is-invalid': errors.name }" aria-describedby="name"
                                placeholder="Enter full name" />
                            <span class="invalid-feedback">{{ errors.name }}</span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <Field v-model="formValues.email" type="text" name="email" id="email" class="form-control"
                                :class="{ 'is-invalid': errors.email }" aria-describedby="email"
                                placeholder="Enter email" />
                            <span class="invalid-feedback">{{ errors.email }}</span>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <Field v-model="formValues.password" type="text" name="password" id="password"
                                class="form-control" :class="{ 'is-invalid': errors.password }" aria-describedby="password"
                                placeholder="Enter Password" />
                            <span class="invalid-feedback">{{ errors.password }}</span>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </Form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <span>Delete Org</span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <h5>Are you sure you want to delete this use?</h5>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Close
                    </button>
                    <button @click.prevent="deleteOrg" type="submit" class="btn btn-primary">Delete</button>
                </div>

            </div>
        </div>
    </div>
</template>