<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">
        Статистика
      </h1>
      <p class="page-subtitle">
        Обзор заметок, папок, тегов и связей
      </p>
    </div>

    <DashboardStatsPanel />
  </div>
</template>

<script setup lang="ts">
import DashboardStatsPanel from '@/components/dashboard/DashboardStatsPanel.vue'
</script>
