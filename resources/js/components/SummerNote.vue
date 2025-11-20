<script setup>
import { watch } from 'vue';

defineProps({
    work_detail: Object
});


</script>

<template>
    <AdvancedImage :cld-img="workDetail" class="profile-user-img img-circle" />

    <textarea id="summernote" v-bind="work_detail"></textarea>
</template>