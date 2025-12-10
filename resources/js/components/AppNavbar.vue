<script setup>
import { useSettingStore } from '../stores/SettingStore';
import { useAuthUserStore } from '../stores/AuthUserStore';

const settingStore = useSettingStore();
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance);
const authUserStore = useAuthUserStore();

const handleSwitchLayout = () => {
  if (authUserStore.user?.can_multiple_role) {
    authUserStore.switchLayout();
  }
};
</script>

<template>
  <nav
    class="main-header navbar navbar-expand"
    :class="settingStore.theme === 'dark' ? 'navbar-dark' : 'navbar-light'"
  >
    <ul class="navbar-nav">
      <li class="nav-item" id="toggleMenuIcon">
        <a
  class="nav-link"
  href="#"
  role="button"
  @click.prevent="settingStore.toggleMenuIcon"
>
  <i class="fas fa-bars"></i>
</a>

      </li>

      <li class="nav-item">
        <a
          class="nav-link"
          href="#"
          role="button"
          @click.prevent="settingStore.changeTheme"
        >
          <i class="far" :class="settingStore.theme === 'dark' ? 'fa-moon' : 'fa-sun'"></i>
        </a>
      </li>
    </ul>

    <!-- ... -->
  </nav>

</template>
