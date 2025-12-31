import axios from "axios";
import { defineStore } from "pinia";
import { computed } from "vue";
import { useStorage } from "@vueuse/core";
import { useAuthUserStore } from "./AuthUserStore";

const normalizeBool = (v) =>
  ["1", 1, true, "true", "on"].includes(v);

export const useSettingStore = defineStore("SettingStore", () => {
  /**
   * =========================
   * STATE (PERSISTED)
   * =========================
   */
  const setting = useStorage("SettingStore:setting", {
    app_name: "",
    date_format: "YYYY-MM-DD",
    pagination_limit: 10,
    maintenance: false, // 🔑 SELALU BOOLEAN
    environment: "development",
  });

  const loaded = useStorage("SettingStore:loaded", false);

  /**
   * =========================
   * APPLY SETTINGS (SINGLE SOURCE OF TRUTH)
   * =========================
   */
  const applySettings = (payload) => {
    if (!payload) return;

    setting.value = {
      ...setting.value,
      ...payload,
      // 🔑 NORMALISASI FINAL
      maintenance: normalizeBool(payload.maintenance),
    };

    loaded.value = true;
  };

  /**
   * =========================
   * FETCH FROM API (ONCE)
   * =========================
   */
  const getSetting = async () => {
    if (loaded.value) return;

    try {
      const { data } = await axios.get("/api/settings");
      applySettings(data);
    } catch (error) {
      // 🔥 Jika settings gagal → reset total (safety)
      localStorage.clear();
      sessionStorage.clear();
      document.cookie.split(";").forEach((cookie) => {
        const eqPos = cookie.indexOf("=");
        const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie =
          name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
      });
      location.reload();
    }
  };

  /**
   * =========================
   * ENV COMPUTED
   * =========================
   */
  const isProduction = computed(
    () => setting.value.environment === "production"
  );

  const isDevelopment = computed(
    () => setting.value.environment === "development"
  );

  /**
   * =========================
   * THEME & SIDEBAR
   * =========================
   */
  const theme = useStorage("SettingStore:theme", "light");
  const sidebarCollapsed = useStorage(
    "SettingStore:sidebarCollapsed",
    false
  );

  const changeTheme = () => {
    theme.value = theme.value === "light" ? "dark" : "light";
  };

  const applySidebarClass = (collapsed) => {
    const body = document.body;
    if (!body) return;

    body.classList.toggle("sidebar-collapse", collapsed);
  };

  const toggleMenuIcon = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    applySidebarClass(sidebarCollapsed.value);
  };

  // Sync class saat load
  applySidebarClass(sidebarCollapsed.value);

  /**
   * =========================
   * MAINTENANCE BADGE
   * =========================
   */
  const authUserStore = useAuthUserStore();

  const showMaintenanceBadge = computed(() => {
    const role = authUserStore?.user?.role ?? null;
    return setting.value.maintenance === true && role !== "SUPERADMIN";
  });

  /**
   * =========================
   * UTIL
   * =========================
   */
  const resetMaintenance = () => {
    setting.value.maintenance = false;
  };

  return {
    // state
    setting,
    loaded,

    // core
    getSetting,
    applySettings,

    // env
    isProduction,
    isDevelopment,

    // ui
    theme,
    changeTheme,

    sidebarCollapsed,
    toggleMenuIcon,
    applySidebarClass,

    // maintenance
    resetMaintenance,
    showMaintenanceBadge,
  };
});
