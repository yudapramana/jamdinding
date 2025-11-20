<template>
    <div class="input-group mb-3">
        <input type="text" v-bind="searchQuery" class="form-control" placeholder="Cari dokumen...">
        <div class="input-group-append">
            <!-- <button class="btn btn-outline-secondary" type="button" @click="clearSearch"><i class="fas fa-sync"></i></button> -->
            <button class="btn btn-outline-secondary" type="button" @click="clearSearch">Reset</button>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    searchQuery: String,
});

const clearSearch = () => {
    props.searchQuery = '';
};
</script>