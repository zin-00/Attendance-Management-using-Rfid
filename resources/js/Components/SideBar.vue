<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { UsersIcon,HomeIcon,
  ChartBarIcon,MapIcon, ClipboardDocumentCheckIcon, ChevronRightIcon, ChevronLeftIcon, CalendarDaysIcon,
DocumentCheckIcon, ClipboardDocumentListIcon} from '@heroicons/vue/24/outline';
import { route } from 'ziggy-js';
import Records from '@/Pages/Attendances/Records.vue';

const sidebarState = ref(localStorage.getItem('sidebarState') || 'full');

watch(sidebarState, (newState) => {
  localStorage.setItem('sidebarState', newState);
});

const toggleSidebar = () => {
  sidebarState.value = sidebarState.value === 'full' ? 'icon' : 'full';
};

const closeSidebar = () => {
  sidebarState.value = 'closed';
};

const screenWidth = ref(window.innerWidth);

const updateScreenWidth = () => {
  screenWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener('resize', updateScreenWidth);
  
  if (screenWidth.value < 1024) {
    sidebarState.value = 'closed';
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', updateScreenWidth);
});
</script>

<template>
  <div>
    <!-- Toggle Button -->
    <button 
        @click="toggleSidebar" 
        class="fixed top-1/2 left-4 z-40 p-2 rounded-lg text-gray-700 focus:outline-none  transition-all duration-300 transform -translate-y-1/2"
        :class="{
            'left-[0px]': sidebarState === 'closed',
            'left-[40px]': sidebarState === 'icon',
            'left-[210px]': sidebarState === 'full'
        }"
        >
      <ChevronLeftIcon v-if="sidebarState === 'full'" class="h-6 w-6 font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
      </ChevronLeftIcon>
      <ChevronRightIcon v-else-if="sidebarState === 'icon'" class="h-6 w-6 font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
      </ChevronRightIcon>
      <ChevronRightIcon v-else class="h-6 w-6 font-bold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </ChevronRightIcon>
    </button>
    
    <!-- Overlay for mobile/small screens -->
    <div 
      v-if="sidebarState !== 'closed' && screenWidth < 1024" 
      @click="closeSidebar" 
      class="fixed inset-0 bg-gray-900 bg-opacity-50 z-30 lg:hidden"
    ></div>
    
    <!-- Sidebar -->
    <div 
      class="fixed top-16 left-0 z-30 h-[calc(100vh-4rem)] transition-all duration-300 ease-in-out bg-white dark:bg-gray-800 shadow-lg overflow-y-auto"
      :class="{
        'w-60': sidebarState === 'full',
        'w-16': sidebarState === 'icon',
        'w-0': sidebarState === 'closed',
        'translate-x-0': sidebarState !== 'closed',
        '-translate-x-full': sidebarState === 'closed'
      }"
    >
      <div class="py-4">
        <ul class="space-y-2 font-medium px-2">
          <li>
            <Link href="/dashboard" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" :class="{'bg-gray-100': route().current('dashboard')}">
              <HomeIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />

              </HomeIcon>
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                Dashboard
              </span>
            </Link>
          </li>
          <li>
            <Link href="/employees" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" :class="{'bg-gray-100': route().current('employees.index')}">
              <UsersIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </UsersIcon>
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                Employees
              </span>
            </Link>
          </li>
          <li>
            <Link href="/attendance/view" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
              <ClipboardDocumentListIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </ClipboardDocumentListIcon  >
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                Live Attendance
              </span>
            </Link>
          </li>
          <li>
            <Link href="/attendance-records" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
              <DocumentCheckIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </DocumentCheckIcon >
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                Attendance Records
              </span>
            </Link>
          </li>
          <li>
            <Link href="/schedules" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
              <CalendarDaysIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </CalendarDaysIcon >
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                Schedule
              </span>
            </Link>
          </li>
          <li>
            <Link href="/attendance/history" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
              <MapIcon class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </MapIcon>
              
              <span class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap" :class="{'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full'}">
                History
              </span>
            </Link>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
