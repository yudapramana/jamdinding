
<script setup>
import { watch } from 'vue';
import { AdvancedImage } from '@cloudinary/vue';
import { Cloudinary } from '@cloudinary/url-gen';

const cld = new Cloudinary({
    cloud: {
        cloudName: "kemenagpessel",
        uploadPreset: "profile_picture_pegawai"
    }
})

const props = defineProps({
    imageName: String
});

const myImg = cld.image(props.imageName);

</script>

<template>
    <AdvancedImage :cld-img="myImg" class="profile-user-img img-circle" />
</template>