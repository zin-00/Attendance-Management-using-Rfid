<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router} from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { DateTime } from 'luxon';

// Data references
const attendanceRecords = ref([]);
const totalPresent = ref(0);
const totalEmployees = ref(0);
const total_active = ref(0);
const totalAbsent = ref(0);
const employees = ref([]);
const selectedEmployee = ref('');
const dateFilter = ref(new Date().toISOString().split('T')[0]);
const monthlyAttendance = ref([]);
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const isLoading = ref(false);
const attendanceStats = ref({
  weeklyPresent: 0,
  weeklyAbsent: 0,
  monthlyPresent: 0,
  monthlyAbsent: 0
});
const timeRangeFilter = ref('today');
const searchQuery = ref('');
const current_date = ref(DateTime.now().toISODate());
const view_employees = () => router.get(route('employees.index'));
const view_active_employees = () => router.get(route('employees.index', {status: 'active'}));

const view_present_today = () => router.get(route('history.list', {status: 'present', date: current_date}));
const view_absent_today = () => router.get(route('history.list', {status: 'absent', date: current_date}));

const filteredAttendanceRecords = computed(() => {
  if (!searchQuery.value) return attendanceRecords.value;
  return attendanceRecords.value.filter(record =>
    (record.employee_name?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
    (record.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
    (record.rfid_tag?.toLowerCase().includes(searchQuery.value.toLowerCase()))
  );
});

const attendanceRate = computed(() => {
  return totalEmployees.value > 0 ? Math.round((totalPresent.value / totalEmployees.value) * 100) : 0;
});

const fetchAttendance = async (range = 'today') => {
  isLoading.value = true;
  try {
    const response = await axios.get(`/api/attendance/${range}`);
    console.log('Attendance response:', response.data);
    attendanceRecords.value = response.data.attendance || [];
    totalPresent.value = response.data.totalPresent || 0;
    totalAbsent.value = response.data.totalAbsent || 0;
    totalEmployees.value = response.data.totalEmployees || 0;
  } catch (error) {
    console.error('Error fetching attendance:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchEmployees = async () => {
  try {
    const response = await axios.get('/api/employees');
    employees.value = response.data.employees || [];
    
    const countResponse = await axios.get('/api/employees/count');
    total_active.value = countResponse.data.active_employees || 0;
    totalEmployees.value = countResponse.data.total || 0;
    totalAbsent.value = countResponse.data.total_absent || 0;
    totalPresent.value = countResponse.data.total_present || 0;
  } catch (error) {
    console.error('Error fetching employees:', error);
  }
};

const filterAttendance = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('/api/attendance/filter', {
      params: { employee_id: selectedEmployee.value, date: dateFilter.value }
    });
    attendanceRecords.value = response.data.attendance || [];
    totalPresent.value = response.data.totalPresent || 0;
    totalAbsent.value = response.data.totalAbsent || 0;
  } catch (error) {
    console.error('Error filtering attendance:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchMonthlyAttendance = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('/api/employees/attendance/monthly', {
      params: { month: selectedMonth.value }
    });
    console.log("API Response:", response.data);
    monthlyAttendance.value = response.data || [];
  } catch (error) {
    console.error('Error fetching monthly attendance:', error);
    console.error('Full error details:', error.response);
  } finally {
    isLoading.value = false;
  }
};

const fetchAttendanceStats = async () => {
  try {
    const response = await axios.get('/api/summary');
    console.log('Attendance summary:', response.data);
    attendanceStats.value = response.data || {
      weeklyPresent: 0,
      weeklyAbsent: 0,
      monthlyPresent: 0,
      monthlyAbsent: 0,
    };
  } catch (error) {
    console.error('Error fetching attendance stats:', error);
  }
};
const handleClick = () => {
  console.log("Card clicked!");
  alert("Card clicked!");
};

const setTimeRange = (range) => {
  timeRangeFilter.value = range;
  fetchAttendance(range);
};

const clearFilters = () => {
  selectedEmployee.value = '';
  dateFilter.value = new Date().toISOString().split('T')[0];
  searchQuery.value = '';
  fetchAttendance(timeRangeFilter.value);
};

const exportData = (format) => {
  // Placeholder for export functionality
  window.open(`/api/attendance/export?format=${format}&date=${dateFilter.value}&employee=${selectedEmployee.value}`, '_blank');
};

watch(selectedMonth, fetchMonthlyAttendance);

// Single onMounted hook to prevent duplicate calls
onMounted(() => {
  fetchAttendance('today');
  fetchEmployees();
  fetchAttendanceStats();
  fetchMonthlyAttendance();
});
</script>

<template>
  <Head title="Employee Attendance Dashboard" />
  <AuthenticatedLayout>
    <div class="p-2 md:p-4 lg:p-6 bg-gray-100 min-h-screen">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 md:mb-6">
        <h1 class="text-xl md:text-2xl font-bold text-black mb-2 sm:mb-0">Attendance Dashboard</h1>
        <div class="flex space-x-2">
          <button @click="exportData('pdf')" class="px-3 py-1.5 md:px-4 md:py-2 bg-black text-white rounded-md hover:bg-gray-800 transition text-sm md:text-base">
            Export PDF
          </button>
          <button @click="exportData('csv')" class="px-3 py-1.5 md:px-4 md:py-2 bg-gray-700 text-white rounded-md hover:bg-gray-800 transition text-sm md:text-base">
            Export CSV
          </button>
        </div>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4 mb-4 md:mb-6">
        <!-- Metric cards with more responsive design -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-3 md:p-5 bg-black" @click="view_present_today()">
            <h2 class="text-base md:text-lg font-medium text-white mb-1">Present Today</h2>
            <div class="flex items-end justify-between">
              <p class="text-2xl md:text-3xl font-bold text-white">{{ totalPresent }}</p>
              <span class="text-sm md:text-base text-gray-300">Employees</span>
            </div>
          </div>
          <div class="px-3 md:px-5 py-2 md:py-3 bg-gray-800 text-gray-200 text-sm md:text-base">
            <span>+{{ attendanceStats.weeklyPresent }} this week</span>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-3 md:p-5 bg-gray-900" @click="view_absent_today()">
            <h2 class="text-base md:text-lg font-medium text-white mb-1">Absent Today</h2>
            <div class="flex items-end justify-between">
              <p class="text-2xl md:text-3xl font-bold text-white">{{ totalAbsent }}</p>
              <span class="text-sm md:text-base text-gray-300">Employees</span>
            </div>
          </div>
          <div class="px-3 md:px-5 py-2 md:py-3 bg-gray-800 text-gray-200 text-sm md:text-base">
            <span>+{{ attendanceStats.weeklyAbsent }} this week</span>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-3 md:p-5 bg-black" @click="handleClick()">
            <h2 class="text-base md:text-lg font-medium text-white mb-1">Attendance Rate</h2>
            <div class="flex items-end justify-between">
              <p class="text-2xl md:text-3xl font-bold text-white">{{ attendanceRate }}%</p>
              <span class="text-sm md:text-base text-gray-300">Today</span>
            </div>
          </div>
          <div class="px-3 md:px-5 py-2 md:py-3 bg-gray-800 text-gray-200 text-sm md:text-base">
            <span>Monthly avg: {{ attendanceStats.monthlyPresent }}%</span>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-3 md:p-5 bg-gray-900" @click="view_employees()">
            <h2 class="text-base md:text-lg font-medium text-white mb-1">Total Employees</h2>
            <div class="flex items-end justify-between">
              <p class="text-2xl md:text-3xl font-bold text-white">{{ totalEmployees }}</p>
              <span class="text-sm md:text-base text-gray-300">Registered</span>
            </div>
          </div>
          <div class="px-3 md:px-5 py-2 md:py-3 bg-gray-800 text-gray-200 text-sm md:text-base" @click="view_active_employees()">
            <span>{{ total_active }} active</span>
          </div>
        </div>
      </div>
      
      <!-- Filters with more responsive design -->
      <div class="bg-white rounded-lg shadow p-3 md:p-6 mb-4 md:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 md:mb-4">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-2 sm:mb-0">Attendance Records</h2>
          
          <div class="flex flex-wrap mt-2 sm:mt-0 gap-2">
            <button 
              @click="setTimeRange('today')" 
              class="px-2 md:px-3 py-1 rounded-md transition text-sm md:text-base"
              :class="timeRangeFilter === 'today' ? 'bg-black text-white' : 'bg-gray-200 hover:bg-gray-300'">
              Today
            </button>
            <button 
              @click="setTimeRange('week')" 
              class="px-2 md:px-3 py-1 rounded-md transition text-sm md:text-base"
              :class="timeRangeFilter === 'week' ? 'bg-black text-white' : 'bg-gray-200 hover:bg-gray-300'">
              This Week
            </button>
            <button 
              @click="setTimeRange('month')" 
              class="px-2 md:px-3 py-1 rounded-md transition text-sm md:text-base"
              :class="timeRangeFilter === 'month' ? 'bg-black text-white' : 'bg-gray-200 hover:bg-gray-300'">
              This Month
            </button>
          </div>
        </div>
        
        <!-- More responsive grid for filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-3 md:mb-4">
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select v-model="selectedEmployee" class="w-full border border-gray-300 rounded-md p-1.5 md:p-2 text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
              <option value="">All Employees</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ employee.first_name }} {{ employee.last_name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" v-model="dateFilter" class="w-full border border-gray-300 rounded-md p-1.5 md:p-2 text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500" />
          </div>
          
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" v-model="searchQuery" placeholder="Search by name or RFID..." class="w-full border border-gray-300 rounded-md p-1.5 md:p-2 text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500" />
          </div>
          
          <div class="flex items-end space-x-2">
            <button @click="filterAttendance" class="flex-1 bg-black hover:bg-gray-800 text-white font-medium py-1.5 md:py-2 px-3 md:px-4 rounded-md transition text-sm md:text-base">
              Apply Filters
            </button>
            <button @click="clearFilters" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-1.5 md:py-2 px-3 md:px-4 rounded-md transition text-sm md:text-base">
              Clear
            </button>
          </div>
        </div>
      </div>
      
      <!-- Responsive table container -->
      <div class="bg-white rounded-lg shadow overflow-hidden mb-4 md:mb-6">
        <div class="p-3 md:p-6">
          <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4">Attendance Records</h2>
          
          <div v-if="isLoading" class="py-4 md:py-6 flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 md:h-10 md:w-10 border-t-2 border-b-2 border-black"></div>
          </div>
          
          <div v-else-if="filteredAttendanceRecords.length === 0" class="py-4 md:py-6 text-center text-gray-500 text-sm md:text-base">
            No attendance records found for the selected criteria.
          </div>
          
          <!-- Table with responsive scrolling -->
          <div v-else class="overflow-x-auto -mx-3 md:-mx-6">
            <div class="inline-block min-w-full align-middle p-3 md:p-6">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-3 md:px-6 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Employee
                    </th>
                    <th scope="col" class="px-3 md:px-6 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      RFID Tag
                    </th>
                    <th scope="col" class="px-3 md:px-6 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th scope="col" class="px-3 md:px-6 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th scope="col" class="px-3 md:px-6 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Time
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <!-- Table rows with responsive spacing -->
                  <tr v-for="record in filteredAttendanceRecords" :key="record.rfid_tag" class="hover:bg-gray-50">
                    <td class="px-3 md:px-6 py-2 md:py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="h-8 w-8 md:h-10 md:w-10 rounded-full bg-black text-white flex items-center justify-center font-semibold text-xs md:text-sm">
                          {{ record.employee_name ? record.employee_name.charAt(0) : (record.first_name ? record.first_name.charAt(0) : '?') }}
                        </div>
                        <div class="ml-2 md:ml-4">
                          <div class="text-xs md:text-sm font-medium text-gray-900">
                            {{ record.employee_name || (record.first_name + ' ' + (record.last_name || '')) || 'Unknown' }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-3 md:px-6 py-2 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                      {{ record.rfid_tag || 'N/A' }}
                    </td>
                    <td class="px-3 md:px-6 py-2 md:py-4 whitespace-nowrap">
                      <span 
                        class="px-1.5 md:px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="record.status === 'Present' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-800'">
                        {{ record.status || 'Unknown' }}
                      </span>
                    </td>
                    <td class="px-3 md:px-6 py-2 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                      {{ record.date || new Date().toLocaleDateString() }}
                    </td>
                    <td class="px-3 md:px-6 py-2 md:py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                      {{ record.time || 'N/A' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Monthly Report with responsive adjustments -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-3 md:p-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 md:mb-4">
            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-2 sm:mb-0">Monthly Attendance Report</h2>
            
            <div class="flex mt-2 sm:mt-0 space-x-2 items-center">
              <input type="month" v-model="selectedMonth" class="border border-gray-300 rounded-md p-1.5 md:p-2 text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500" />
              <button @click="fetchMonthlyAttendance" class="bg-black hover:bg-gray-800 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-md transition text-sm md:text-base">
                View
              </button>
            </div>
          </div>
          
          <div v-if="isLoading" class="py-4 md:py-6 flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 md:h-10 md:w-10 border-t-2 border-b-2 border-black"></div>
          </div>
          
          <div v-else-if="monthlyAttendance.length === 0" class="py-4 md:py-6 text-center text-gray-500 text-sm md:text-base">
            No monthly attendance records found.
          </div>
          
          <div v-else class="overflow-x-auto w-full transition-all duration-300 ease-in-out">
            <div class="inline-block w-full align-middle p-3 md:p-6">
              <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-2 md:px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Employee
                    </th>
                    <th scope="col" class="px-2 md:px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Days Present
                    </th>
                    <th scope="col" class="px-2 md:px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Days Absent
                    </th>
                    <th scope="col" class="px-2 md:px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Attendance Rate
                    </th>
                    <th scope="col" class="px-2 md:px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Working Days
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="record in monthlyAttendance" :key="record.id" class="hover:bg-gray-50">
                    <td class="px-2 md:px-6 py-2 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="h-8 w-8 md:h-10 md:w-10 rounded-full bg-black text-white flex items-center justify-center font-semibold text-xs md:text-sm">
                          {{ record.first_name ? record.first_name.charAt(0) : '?' }}
                        </div>
                        <div class="ml-2 md:ml-4">
                          <div class="text-xs md:text-sm font-medium text-gray-900">
                            {{ record.first_name }} {{ record.last_name || '' }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-2 md:px-6 py-2 whitespace-nowrap">
                      <div class="text-xs md:text-sm text-black font-medium">{{ record.days_present || 0 }}</div>
                    </td>
                    <td class="px-2 md:px-6 py-2 whitespace-nowrap">
                      <div class="text-xs md:text-sm text-gray-600 font-medium">{{ record.days_absent || 0 }}</div>
                    </td>
                    <td class="px-2 md:px-6 py-2 whitespace-nowrap">
                      <div class="w-full bg-gray-200 rounded-full h-1.5 md:h-2.5">
                        <div class="bg-black h-1.5 md:h-2.5 rounded-full" 
                             :style="{ width: `${record.days_present && (record.days_present + record.days_absent) > 0 ? 
                                       Math.round((record.days_present / (record.days_present + record.days_absent)) * 100) : 0}%` }">
                        </div>
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ record.days_present && (record.days_present + record.days_absent) > 0 ? 
                           Math.round((record.days_present / (record.days_present + record.days_absent)) * 100) : 0 }}%
                      </div>
                    </td>
                    <td class="px-2 md:px-6 py-2 whitespace-nowrap text-xs md:text-sm text-gray-500">
                      {{ (record.days_present || 0) + (record.days_absent || 0) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>