<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useToast } from 'vue-toast-notification';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { UnExport } from '@kalimahapps/vue-icons';

const toast = useToast();
const isLoading = ref(false);
const filter_type = ref('all');

const pagination = ref({});

// Date filter states
const selectedDate = ref(new Date().toISOString().split('T')[0]); // Today's date in YYYY-MM-DD
const selectedMonth = ref(new Date().getMonth() + 1); // Current month (1-12)
const selectedYear = ref(new Date().getFullYear()); // Current year
const filterMode = ref('day'); // 'day', 'month', or 'year'

const props = defineProps({
    summaries: {
        type: Object,
        default: () => ({ data: [] })
    },
    date: {
        type: String,
        default: new Date().toISOString().split('T')[0]
    }
});

const summaries = ref([]);

onMounted(() => {
    summaries.value = props.summaries.data || [];
    pagination.value = {
        links: props.summaries.links || [],
        from: props.summaries.from || 0,
        current_page: props.summaries.current_page || 1,
        last_page: props.summaries.last_page || 1
    };
    
    if (props.date) {
        selectedDate.value = props.date;
    }
    
    RealtimeListener();
});

// Generate arrays for month and year dropdowns
const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' }
];

const years = computed(() => {
    const currentYear = new Date().getFullYear();
    const yearList = [];
    for (let i = currentYear - 5; i <= currentYear; i++) {
        yearList.push(i);
    }
    return yearList;
});

const RealtimeListener = () => {
    window.Echo.channel('attendance-summaries')
    .listen('AttendanceSummaryEvent', (e) => {
        summaries.value.unshift(e.summary);
        
        // if (summaries.value.length > 50) {
        //     summaries.value.pop();
        // }
    });
};

const fetchSummaries = async (url = null, params = {}) => {
    try {
        isLoading.value = true;
        
        // Build query parameters based on active filter
        let queryParams = { ...params };
        if (filterMode.value === 'day') {
            queryParams.date = selectedDate.value;
        } else if (filterMode.value === 'month') {
            queryParams.month = selectedMonth.value;
            queryParams.year = selectedYear.value;
        } else if (filterMode.value === 'year') {
            queryParams.year = selectedYear.value;
        }
        
        if (filter_type.value !== 'all') {
            queryParams.time_of_day = filter_type.value;
        }
        
        // Make the API request
        const response = await axios.get(url || `/attendace-records`, {
            params: queryParams
        });
        
        summaries.value = response.data.summaries.data;
        pagination.value = {
            links: response.data.summaries.links,
            from: response.data.summaries.from,
            current_page: response.data.summaries.current_page,
            last_page: response.data.summaries.last_page
        };
    } catch (error) {
        toast.error('Failed to fetch attendance summaries data');
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

const goToPage = (url) => {
    if (url) {
        fetchSummaries(url);
    }
};

const applyFilters = () => {
    fetchSummaries();
};

const exportData = (format) => {
    // Build query parameters based on active filter
    let queryParams = {};
    if (filterMode.value === 'day') {
        queryParams.date = selectedDate.value;
    } else if (filterMode.value === 'month') {
        queryParams.month = selectedMonth.value;
        queryParams.year = selectedYear.value;
    } else if (filterMode.value === 'year') {
        queryParams.year = selectedYear.value;
    }
    
    if (filter_type.value !== 'all') {
        queryParams.time_of_day = filter_type.value;
    }
    
    queryParams.format = format;
    
    // Build query string
    const queryString = Object.keys(queryParams)
        .map(key => `${key}=${encodeURIComponent(queryParams[key])}`)
        .join('&');
    
    // Open export URL in new tab/window
    window.open(`${route('attendances.export')}?${queryString}`, '_blank');
};

const filteredSummaries = computed(() => {
    return summaries.value.filter((record) => {
        if (!record) return false;
        
        if (filter_type.value === 'all') return true;
        
        // Check if morning status exists for morning filter
        if (filter_type.value === 'morning') {
            return record.morning_status !== null;
        }
        
        // Check if afternoon status exists for afternoon filter
        if (filter_type.value === 'afternoon') {
            return record.afternoon_status !== null;
        }
        
        return true;
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

const formatDate = (dateString, locale = 'en-US', options = {}) => {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        return 'Invalid Date';
    }

    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };

    return date.toLocaleDateString(locale, { ...defaultOptions, ...options });
};

// Format hours
const formatHours = (hours) => {
    if (!hours && hours !== 0) return 'N/A';
    
    // Format hours with 2 decimal places
    return parseFloat(hours).toFixed(2);
};

// Watch for filter mode changes to update UI
watch(filterMode, (newMode) => {
    // Reset other filters when mode changes
    if (newMode === 'day') {
        selectedDate.value = new Date().toISOString().split('T')[0];
    } else if (newMode === 'month') {
        selectedMonth.value = new Date().getMonth() + 1;
        selectedYear.value = new Date().getFullYear();
    } else if (newMode === 'year') {
        selectedYear.value = new Date().getFullYear();
    }
});

onUnmounted(() => {
    window.Echo.leaveChannel('attendance-summaries');
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 md:py-8 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-4 bg-white shadow-md rounded-lg">
                <!-- Header with filter -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
                    <h2 class="text-lg font-semibold">Attendance Summary Records</h2>
                    <div class="flex flex-wrap gap-2">
                        <SecondaryButton @click="exportData('csv')" class="w-full sm:w-auto flex items-center gap-1">
                            <ArrowDownTrayIcon class="h-5 w-5" />
                            <span>CSV</span>
                        </SecondaryButton>
                        <SecondaryButton @click="exportData('pdf')" class="w-full sm:w-auto flex items-center gap-1">
                            <UnExport class="h-5 w-5" />
                            <span>PDF</span>
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6 p-4 border rounded-lg bg-gray-50">
                    <div class="flex flex-col md:flex-row gap-4 mb-4">
                        <!-- Filter Type Tabs -->
                        <div class="flex flex-wrap gap-2 mb-2 md:mb-0">
                            <button 
                                @click="filterMode = 'day'" 
                                class="px-3 py-1 text-sm rounded-md border"
                                :class="filterMode === 'day' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                            >
                                By Day
                            </button>
                            <button 
                                @click="filterMode = 'month'" 
                                class="px-3 py-1 text-sm rounded-md border"
                                :class="filterMode === 'month' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                            >
                                By Month
                            </button>
                            <button 
                                @click="filterMode = 'year'" 
                                class="px-3 py-1 text-sm rounded-md border"
                                :class="filterMode === 'year' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                            >
                                By Year
                            </button>
                            <select  
                                id="time_filter"
                                v-model="filter_type" 
                                class="border text-sm p-1 rounded w-[100px]"
                            >
                                <option value="all">All Times</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                            <div v-if="filterMode === 'day'" class="flex flex-col md:items-center md:flex-row gap-2">
                                <!-- <label for="date_select" class="text-sm text-gray-700">Select Date:</label> -->
                                <input 
                                    type="date" 
                                    id="date_select"
                                    v-model="selectedDate" 
                                    class="border p-1 rounded"
                                />
                            </div>
                            <PrimaryButton 
                                @click="applyFilters" 
                                :disabled="isLoading"
                                class="flex items-center gap-1"
                            >
                                <span v-if="isLoading" class="animate-spin">⏳</span>
                                <span>{{ isLoading ? 'Loading...' : 'Apply Filters' }}</span>
                            </PrimaryButton>
                        </div>

                    </div>

                    <!-- Date/Month/Year Filters -->
                    <div class="flex flex-col md:flex-row gap-4 mb-4">
                        <!-- Month filter -->
                        <div v-if="filterMode === 'month'" class="flex flex-wrap gap-4">
                            <div class="flex flex-col md:items-center md:flex-row gap-2">
                                <label for="month_select" class="text-sm text-gray-700">Month:</label>
                                <select 
                                    id="month_select"
                                    v-model="selectedMonth" 
                                    class="border p-1 rounded"
                                >
                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                        {{ month.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex flex-col md:items-center md:flex-row gap-2">
                                <label for="year_select_month" class="text-sm text-gray-700">Year:</label>
                                <select 
                                    id="year_select_month"
                                    v-model="selectedYear" 
                                    class="border p-1 rounded w-20"
                                >
                                    <option v-for="year in years" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Year filter -->
                        <div v-if="filterMode === 'year'" class="flex flex-col md:items-center md:flex-row gap-2">
                            <label for="year_select" class="text-sm text-gray-700">Select Year:</label>
                            <select 
                                id="year_select"
                                v-model="selectedYear" 
                                class="border p-1 rounded w-20"
                            >
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Apply filter button -->
                    <div class="flex justify-end">
                      
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border text-[10px]">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-1">#</th>
                                <th class="border p-1">Name</th>
                                <th class="border p-1">Date</th>
                                <th class="border p-1">Morning Status</th>
                                <th class="border p-1">Afternoon Status</th>
                                <th class="border p-1">Evening Status</th>
                                <th class="border p-1">Final Status</th>
                                <th class="border p-1">Total Hours</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(summary, index) in summaries" :key="summary.id" class="hover:bg-gray-50 text-center">
                                <td class="border p-2">{{ pagination.from ? pagination.from + index : index + 1 }}</td>
                                <td class="border p-2">{{ summary.employee_name || 'N/A' }}</td>
                                <td class="border p-2">{{ formatDate(summary.date) }}</td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(summary.morning_status)">
                                        {{ summary.morning_status || 'N/A' }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(summary.afternoon_status)">
                                        {{ summary.afternoon_status || 'N/A' }}
                                    </span>
                                </td>
                                <td class="border p-2">{{ formatHours(summary.total_hours) }}</td>
                            </tr>

                            <tr v-if="filteredSummaries.length === 0">
                                <td class="border p-2 text-center py-8 text-gray-500" colspan="8">
                                    No attendance summary records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Loading indicator -->
                <div v-if="isLoading" class="flex justify-center my-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.links?.length > 3" class="mt-4 flex justify-center space-x-1 text-sm">
                    <button 
                        v-for="link in pagination.links" 
                        :key="link.label" 
                        @click="goToPage(link.url)" 
                        :disabled="!link.url || isLoading"
                        class="px-2 py-1 border rounded" 
                        :class="{
                            'bg-blue-500 text-white': link.active, 
                            'text-gray-700 hover:bg-gray-100': !link.active && !isLoading,
                            'cursor-not-allowed opacity-50': !link.url || isLoading
                        }">
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>