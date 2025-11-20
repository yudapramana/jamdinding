<template>
    <transition name="fade">
        <div class="loading-overlay">
            <div class="spinner"></div>
        </div>
    </transition>
</template>

<style scoped>
.login-logo {
    margin-top: -50px !important;
    margin-bottom: 0px !important;
}

.loading-overlay {
    position: fixed;
    z-index: 999;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.85);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
    color: #555;
    transition: opacity 0.3s;
}

.spinner {
    border: 4px solid #ccc;
    border-top: 4px solid #28a745;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 0.8s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>