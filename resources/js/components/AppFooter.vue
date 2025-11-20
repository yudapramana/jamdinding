<script setup>
import { useAuthUserStore } from '../stores/AuthUserStore';


const authUserStore = useAuthUserStore();

defineProps({
    settings: Object
})

</script>
<template>
    <footer class="main-footer">

        <div class="float-right d-none d-sm-inline">
            V.1.0.1
        </div>

        <strong>Copyright &copy; 2024 <a href="#"> {{ settings?.app_name }}</a>.</strong> All rights
        reserved.
    </footer>
</template>