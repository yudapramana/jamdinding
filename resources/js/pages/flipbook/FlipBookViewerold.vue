<script setup>
import Flipbook from 'flipbook-vue'
</script>

<template>
  <Flipbook class="flipbook" :pages="['https://pdfobject.com/pdf/sample.pdf', 'https://images.unsplash.com/photo-1527769929977-c341ee9f2033?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8YTQlMjBwYXBlcnxlbnwwfHwwfHx8MA%3D%3D', 'https://images.unsplash.com/photo-1470790376778-a9fbc86d70e2?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTF8fGE0JTIwcGFwZXJ8ZW58MHx8MHx8fDA%3D', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSeRvkAVKpUVFJfC-GtxN4Msq8JtqUXDHhKS9_CF66GKaGtaT8D92TJyzskL3198Vt6pC0', 'https://images.unsplash.com/photo-1503891617560-5b8c2e28cbf6?fm=jpg&q=60&w=3000&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTRpVLH-SJEd-VzwbSenLBMlBKSf8BGl_TmFg&s']" />
</template>

<style scoped>
.flipbook {
  width: auto;
  height: 500px;
}
</style>