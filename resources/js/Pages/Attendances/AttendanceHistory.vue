<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useToast } from 'vue-toast-notification';
import { DateTime } from 'luxon';

const route = window.route || (() => ({}));
const params = route().params || {};

const attendance = ref([]);
const pagination = ref({});
const toast = useToast();
const isLoading = ref(false);

let interval = null;

const filters = ref({
  timeOfDay: 'all',      
  status: 'all',         
  type: 'all',            
  searchQuery: '',       
  dateRange: {
    start: null,
    end: null
  },
  month: DateTime.now().month,
  year: DateTime.now().year
});

const statusOptions = ['all', 'present', 'late', 'absent'];
const typeOptions = ['all', 'check-in', 'check-out'];

const months = computed(() => {
  return Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: DateTime.local(2000, i + 1, 1).toFormat('MMMM')
  }));
});

const years = computed(() => {
  const currentYear = DateTime.now().year;
  return Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);
});

// debounce for search query
let searchTimeout = null;
const debouncedSearch = (value) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.value.searchQuery = value;
  }, 300);
};

const fetchAttendance = async (url = null) => {
  if (!url) {
    const queryParams = new URLSearchParams({
      month: filters.value.month,
      year: filters.value.year,
      search: filters.value.searchQuery,
      status: filters.value.status !== 'all' ? filters.value.status : '',
      type: filters.value.type !== 'all' ? filters.value.type : '',
      time_of_day: filters.value.timeOfDay !== 'all' ? filters.value.timeOfDay : ''
    });
    
    if (filters.value.dateRange.start) {
      queryParams.append('start_date', filters.value.dateRange.start);
    }
    if (filters.value.dateRange.end) {
      queryParams.append('end_date', filters.value.dateRange.end);
    }
    
    url = `/api/attendance/employees/history?${queryParams.toString()}`;
  }

  try {
    isLoading.value = true;
    const response = await axios.get(url);
    attendance.value = response.data.data;
    pagination.value = response.data;
    
    // toast.success(`Found ${response.data.total} attendance records`);
  } catch (e) {
    console.error('Failed to fetch attendance data:', e);
    // toast.error('Failed to load attendance data');
  } finally {
    isLoading.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    timeOfDay: 'all',
    status: 'all',
    type: 'all',
    searchQuery: '',
    dateRange: {
      start: null,
      end: null
    },
    month: DateTime.now().month,
    year: DateTime.now().year
  };
  
  fetchAttendance();
};

const goToPage = (url) => {
  if (url) {
    fetchAttendance(url);
  }
};

const filteredAttendance = computed(() => {
  return attendance.value.filter((record) => {
    const name = `${record.employee?.first_name ?? ''} ${record.employee?.last_name ?? ''}`.toLowerCase();
    return name.includes(filters.value.searchQuery.toLowerCase());
  });
});

const getStatusClass = (status) => {
  switch(status?.toLowerCase()) {
    case 'present': return 'bg-green-100 text-green-800';
    case 'late': return 'bg-yellow-100 text-yellow-800';
    case 'absent': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getTypeClass = (type) => {
  switch(type?.toLowerCase()) {
    case 'check-in': return 'bg-blue-100 text-blue-800';
    case 'check-out': return 'bg-purple-100 text-purple-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatDate = (dateString) => {
  return DateTime.fromISO(dateString).toLocaleString(DateTime.DATETIME_FULL);
};

const handleFilterChange = () => {
  clearInterval(interval);
  fetchAttendance();
  interval = setInterval(fetchAttendance, 30000);
};

watch(() => [...Object.values(filters.value), filters.value.dateRange], 
  handleFilterChange, 
  { deep: true }
);

onMounted(() => {
  if (params.status && statusOptions.includes(params.status)) {
    filters.value.status = params.status;
  }
  
  if (params.date) {
    const date = params.date || DateTime.now().toISODate();
    filters.value.dateRange.start = date;
    filters.value.dateRange.end = date;
    
    const dateObj = DateTime.fromISO(date);
    if (dateObj.isValid) {
      filters.value.month = dateObj.month;
      filters.value.year = dateObj.year;
    }
  }
  
  fetchAttendance();
  interval = setInterval(fetchAttendance, 30000);
});

onUnmounted(() => {
  clearInterval(interval);
  clearTimeout(searchTimeout);
});

// Export function for reports
const exportAttendance = async (format = 'csv') => {
  try {
    isLoading.value = true;
    toast.info(`Preparing ${format.toUpperCase()} export...`);
    
    const queryParams = new URLSearchParams({
      month: filters.value.month,
      year: filters.value.year,
      search: filters.value.searchQuery,
      status: filters.value.status !== 'all' ? filters.value.status : '',
      type: filters.value.type !== 'all' ? filters.value.type : '',
      time_of_day: filters.value.timeOfDay !== 'all' ? filters.value.timeOfDay : '',
      format: format
    });
    
    if (filters.value.dateRange.start) {
      queryParams.append('start_date', filters.value.dateRange.start);
    }
    if (filters.value.dateRange.end) {
      queryParams.append('end_date', filters.value.dateRange.end);
    }
    
    window.open('/api/attendance/export/pdf', '_blank');
    window.open(url, '_blank');
    
    toast.success(`${format.toUpperCase()} export initiated successfully`);
  } catch (e) {
    console.error('Failed to export attendance data:', e);
    toast.error(`Failed to export as ${format.toUpperCase()}`);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-8 max-w-7xl mx-auto sm:px-4">
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <!-- Header with title and export buttons -->
        <div class="p-4 bg-gray-50 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Attendance History</h2>
            <!-- <div class="flex space-x-2">
              <button 
                @click="exportAttendance('csv')" 
                class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition"
                :disabled="isLoading">
                Export CSV
              </button>
              <button 
                @click="exportAttendance('pdf')" 
                class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-800 transition"
                :disabled="isLoading">
                Export PDF
              </button>
            </div> -->
          </div>
        </div>
        
        <!-- Filters section -->
        <div class="p-4 bg-gray-50 border-b border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
            <!-- Search filter -->
            <div class="col-span-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Search Name</label>
              <input 
                type="text" 
                @input="debouncedSearch($event.target.value)" 
                :value="filters.searchQuery"
                placeholder="Search by name..."
                class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
              />
            </div>
            
            <!-- Month and Year filters -->
            <div class="col-span-1 grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <select 
                  v-model="filters.month" 
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option v-for="month in months" :value="month.value" :key="month.value">
                    {{ month.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select 
                  v-model="filters.year" 
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option v-for="year in years" :value="year" :key="year">{{ year }}</option>
                </select>
              </div>
            </div>
            
            <!-- Date range picker -->
            <div class="col-span-1 grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input 
                  type="date" 
                  v-model="filters.dateRange.start"
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input 
                  type="date" 
                  v-model="filters.dateRange.end"
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                />
              </div>
            </div>
            
            <!-- Status and Type filters -->
            <div class="col-span-1 grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                  v-model="filters.status" 
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option v-for="status in statusOptions" :value="status" :key="status">
                    {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select 
                  v-model="filters.type" 
                  class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option v-for="type in typeOptions" :value="type" :key="type">
                    {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                  </option>
                </select>
              </div>
            </div>
            
            <!-- Time of Day filter -->
            <div class="col-span-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Time of Day</label>
              <select 
                v-model="filters.timeOfDay" 
                class="w-full border border-gray-300 p-2 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="all">All Times</option>
                <option value="morning">Morning</option>
                <option value="afternoon">Afternoon</option>
                <option value="evening">Evening</option>
              </select>
            </div>
            
            <!-- Reset filter button -->
            <div class="col-span-1 flex items-end">
              <button 
                @click="resetFilters" 
                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md"
              >
                Reset Filters
              </button>
            </div>
          </div>
        </div>
        
        <!-- Loading indicator -->
        <div v-if="isLoading" class="flex justify-center items-center p-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
        </div>
        
        <!-- Table with attendance data -->
        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse border text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="border p-2 text-left">#</th>
                <th class="border p-2 text-left">Employee</th>
                <th class="border p-2 text-left">Date & Time</th>
                <th class="border p-2 text-center">Status</th>
                <th class="border p-2 text-center">Type</th>
                <th class="border p-2 text-center">Work Hours Summary</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(attend, index) in filteredAttendance" :key="attend.id" class="hover:bg-gray-50">
                <td class="border p-2 text-left">{{ pagination.from ? pagination.from + index : index + 1 }}</td>
                <td class="border p-2 text-left">
                  <span v-if="attend.employee">
                    {{ attend.employee.first_name }} {{ attend.employee.last_name }}
                  </span>
                  <span v-else class="text-gray-400">N/A</span>
                </td>
                <td class="border p-2 text-left">{{ formatDate(attend.created_at) }}</td>
                <td class="border p-2 text-center">
                  <span 
                    class="px-2 py-1 rounded text-xs font-medium" 
                    :class="getStatusClass(attend.status)"
                  >
                    {{ attend.status ?? 'N/A' }}
                  </span>
                </td>
                <td class="border p-2 text-center">
                  <span 
                    class="px-2 py-1 rounded text-xs font-medium" 
                    :class="getTypeClass(attend.day_type)"
                  >
                    {{ attend.day_type ?? 'N/A' }}
                  </span>
                </td>
                <td class="border p-2 text-center">
                  <span 
                    class="px-2 py-1 rounded text-xs font-medium" 
                  >
                    {{ attend.work_hours ?? 'N/A' }}
                  </span>
                </td>
              </tr>
              <tr v-if="filteredAttendance.length === 0">
                <td class="border p-8 text-center text-gray-500" colspan="5">
                  No attendance records found matching the current filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination controls -->
        <div v-if="pagination.links?.length > 3" class="p-4 flex justify-center space-x-1 bg-gray-50 border-t border-gray-200">
          <button 
            v-for="link in pagination.links" 
            :key="link.label" 
            @click="goToPage(link.url)" 
            :disabled="!link.url || isLoading"
            class="px-3 py-1 border rounded" 
            :class="{
              'bg-blue-500 text-white': link.active, 
              'text-gray-700 hover:bg-gray-100': !link.active && link.url,
              'text-gray-400 cursor-not-allowed': !link.url
            }"
          >
            <span v-html="link.label"></span>
          </button>
        </div>
        
        <!-- Summary info -->
        <div class="p-4 bg-gray-50 border-t border-gray-200 text-sm text-gray-600">
          <div v-if="pagination.total">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} records
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>