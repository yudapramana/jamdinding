<script setup>
import { formatDate } from '../../helper.js';
import { ref } from 'vue';
import { useToastr } from "../../toastr.js";

const toastr = useToastr();

const props = defineProps({
    org: Object,
    index: Number,
    selectAll: Boolean
});

const emit = defineEmits(['orgrDeleted', 'editOrg', 'confirmOrgDeletion']);


const toggleSelection = () => {
    emit('toggleSelection', props.org);
}


</script>


<template>
    <tr >
        <td><input type="checkbox" :checked="selectAll" @change="toggleSelection" /></td>
        <td>{{ index + 1 }}</td>
        <td>{{ org.name }}</td>
        <td>{{ org.email }}</td>
        <td>{{ org.status }}</td>
        <td>
            <a href="#" @click.prevent="$emit('editOrg', org)">
                <i class="fa fa-edit"></i>
            </a>
            <a href="#" @click.prevent="$emit('confirmOrgDeletion', org.id)">
                <i class="fa fa-trash text-danger ml-2"></i>
            </a>
        </td>
    </tr>

    
</template>