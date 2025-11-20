<script setup>
import axios from "axios";
import { ref, onMounted, watch } from "vue";
import { Form, Field } from "vee-validate";
import * as yup from "yup";
import { Bootstrap4Pagination } from 'laravel-vue-pagination';
import { useToastr } from "../../toastr.js";

const toastr = useToastr();

const workUnits = ref({ data: [], current_page: 1, per_page: 10 });
const editing = ref(false);
const formValues = ref({
    id: null,
    unit_name: "",
    unit_code: "",
    parent_unit: null,
});

const form = ref(null);
const searchQuery = ref("");

const getWorkUnits = (page = 1) => {
    axios.get(`/api/work-units?page=${page}`, {
        params: { search: searchQuery.value }
    }).then(response => {
        workUnits.value = response.data;
    });
};

const createSchema = yup.object({
    unit_name: yup.string().required(),
    unit_code: yup.string().required(),
    parent_unit: yup.number().nullable(),
});

const handleSubmit = (values, { resetForm, setErrors }) => {
    if (editing.value) {
        updateWorkUnit(values, setErrors);
    } else {
        createWorkUnit(values, resetForm, setErrors);
    }
};

const createWorkUnit = (values, resetForm, setErrors) => {
    axios.post("/api/work-units", values)
        .then(response => {
            $("#defaultModal").modal("hide");
            resetForm();
            toastr.success("Work Unit created successfully!");
            getWorkUnits(); // refresh
        })
        .catch(error => {
            if (error.response?.data?.errors) setErrors(error.response.data.errors);
        });
};

const updateWorkUnit = (values, setErrors) => {
    axios.put(`/api/work-units/${formValues.value.id}`, values)
        .then(() => {
            $("#defaultModal").modal("hide");
            toastr.success("Work Unit updated successfully!");
            getWorkUnits(); // refresh
        })
        .catch(error => {
            if (error.response?.data?.errors) setErrors(error.response.data.errors);
        });
};

const deleteId = ref(null);

const confirmDelete = id => {
    deleteId.value = id;
    $("#deleteModal").modal("show");
};

const deleteWorkUnit = () => {
    axios.delete(`/api/work-units/${deleteId.value}`)
        .then(() => {
            $("#deleteModal").modal("hide");
            toastr.success("Work Unit deleted successfully!");
            getWorkUnits(); // refresh
        });
};

const editWorkUnit = workUnit => {
    editing.value = true;
    form.value.resetForm();
    formValues.value = { ...workUnit };
    $("#defaultModal").modal("show");
};

const addWorkUnit = () => {
    editing.value = false;
    form.value.resetForm();
    formValues.value = { id: null, unit_name: "", unit_code: "", parent_unit: null };
    $("#defaultModal").modal("show");
};

watch(searchQuery, () => getWorkUnits());
onMounted(() => getWorkUnits());
</script>

<template>
    <div class="content">
        <div class="container-fluid">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light px-3 py-2 rounded">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Work Units</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <button @click="addWorkUnit" class="btn btn-sm btn-primary">+ Tambah Work Unit</button>
                <input v-model="searchQuery" class="form-control form-control-sm w-25" placeholder="Cari Work Unit..." />
            </div>

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Unit Name</th>
                                <th>Unit Code</th>
                                <th>Parent Unit</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="workUnits.data.length === 0">
                                <td colspan="5" class="text-center">No data found.</td>
                            </tr>
                            <tr v-for="(unit, index) in workUnits.data" :key="unit.id">
                                <td>{{ index + 1 + ((workUnits.current_page - 1) * workUnits.per_page) }}</td>
                                <td>{{ unit.unit_name }}</td>
                                <td>{{ unit.unit_code }}</td>
                                <td>{{ unit.parent_unit }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" @click="editWorkUnit(unit)">Edit</button>
                                    <button class="btn btn-sm btn-danger" @click="confirmDelete(unit.id)">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <Bootstrap4Pagination :data="workUnits" @pagination-change-page="getWorkUnits" />
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="modal fade" id="defaultModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editing ? 'Edit' : 'Tambah' }} Work Unit</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <Form ref="form" @submit="handleSubmit" :validation-schema="createSchema" :initial-values="formValues">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Unit Name</label>
                                <Field name="unit_name" class="form-control form-control-sm" />
                            </div>
                            <div class="form-group">
                                <label>Unit Code</label>
                                <Field name="unit_code" class="form-control form-control-sm" />
                            </div>
                            <div class="form-group">
                                <label>Parent Unit</label>
                                <Field name="parent_unit" class="form-control form-control-sm" type="number" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Work Unit</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">Yakin ingin menghapus work unit ini?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                        <button class="btn btn-danger btn-sm" @click="deleteWorkUnit">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
