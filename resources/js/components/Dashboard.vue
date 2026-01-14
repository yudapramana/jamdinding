<script setup>
import { computed, onMounted } from "vue"
import { useAuthUserStore } from "../stores/AuthUserStore.js"
import { useScreenDisplayStore } from "../stores/ScreenDisplayStore.js"
import { useMasterDataStore } from "../stores/MasterDataStore.js"

const authUserStore = useAuthUserStore()
const screenDisplayStore = useScreenDisplayStore()
const masterDataStore = useMasterDataStore()

onMounted(() => {
  if (!masterDataStore.eventStages.length) {
    masterDataStore.loadEventStages()
  }
})

const stages = computed(() => masterDataStore.eventStages)

/* ================= DATE & STATUS ================= */
const today = new Date().toISOString().slice(0, 10)

const stageStatus = (stage) => {
  if (!stage.start_date) return "upcoming"

  if (
    stage.start_date <= today &&
    (!stage.end_date || stage.end_date >= today)
  ) {
    return "active"
  }

  if (stage.end_date && stage.end_date < today) {
    return "done"
  }

  return "upcoming"
}

const stageBadge = (stage) => {
  const s = stageStatus(stage)
  if (s === "active") return "Sedang Berlangsung"
  if (s === "done") return "Selesai"
  return "Akan Datang"
}

const badgeClass = (stage) => {
  const s = stageStatus(stage)
  return {
    "badge-success": s === "active",
    "badge-secondary": s === "done",
    "badge-warning": s === "upcoming",
  }
}

const formatDate = (date) => {
  if (!date) return "-"

  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  })
}
</script>

<template>
  <!-- ================= HEADER ================= -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Beranda</h1>
        </div>
        <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Beranda</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= CONTENT ================= -->
  <div class="content">
    <div class="container-fluid">
      <div class="row">

        <!-- ===== WELCOME CARD ===== -->
        <div class="col-12">
          <div class="card welcome-card">
            <div class="card-body">
              <h4 class="mb-0">
                👋 Selamat datang,
                <strong>{{ authUserStore.user?.name }}</strong>
              </h4>
            </div>
          </div>
        </div>

        <!-- ===== AGENDA EVENT ===== -->
        <div class="col-md-6 col-sm-12 col-lg-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title font-weight-bold text-primary">
                Agenda Event
              </h3>
            </div>

            <div class="card-body p-2">
              <ul class="agenda-list">
                <li
                  v-for="stage in stages"
                  :key="stage.id"
                  class="agenda-item"
                  :class="{ active: stageStatus(stage) === 'active' }"
                >
                  <div class="agenda-icon">
                    <i class="far fa-calendar-alt"></i>
                  </div>

                  <div class="agenda-content">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="agenda-title">
                        {{ stage.name }}
                      </div>

                      <span class="badge" :class="badgeClass(stage)">
                        {{ stageBadge(stage) }}
                      </span>
                    </div>

                    <div class="agenda-date">
                      <i class="far fa-clock mr-1"></i>
                      {{ formatDate(stage.start_date) }}
                      <span v-if="stage.end_date">
                        – {{ formatDate(stage.end_date) }}
                      </span>
                    </div>

                    <div
                      v-if="stage.notes"
                      class="agenda-notes"
                    >
                      {{ stage.notes }}
                    </div>
                  </div>
                </li>

                <li
                  v-if="!stages.length"
                  class="text-center text-muted py-3"
                >
                  Agenda belum tersedia
                </li>
              </ul>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ================= AGENDA EVENT ================= */
.agenda-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.agenda-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 10px;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.agenda-item:last-child {
  border-bottom: none;
}

/* 🔥 ACTIVE STAGE */
.agenda-item.active {
  background: #f0fff4;
  border-left: 4px solid #28a745;
}

.agenda-icon {
  width: 42px;
  height: 42px;
  background: #fff1f0;
  color: #e5533d;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 18px;
}

.agenda-content {
  flex: 1;
}

.agenda-title {
  font-weight: 600;
  font-size: 14px;
  color: #2f3a4a;
}

.agenda-date {
  font-size: 12px;
  color: #6c757d;
  margin-top: 2px;
}

.agenda-notes {
  font-size: 12px;
  color: #495057;
  margin-top: 4px;
}

/* ================= BADGES ================= */
.badge-success { background: #28a745; }
.badge-warning { background: #ffc107; color: #212529; }
.badge-secondary { background: #6c757d; }

/* ================= WELCOME CARD ================= */
.welcome-card {
  background: linear-gradient(135deg, #f8f9fa, #ffffff);
  border-radius: 12px;
}
</style>
