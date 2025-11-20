<template>
  <div class="not-found">
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
    <router-link to="/user/dashboard">Go Home</router-link>
  </div>
</template>

<style scoped>
.not-found {
  text-align: center;
  padding: 50px;
}
</style>