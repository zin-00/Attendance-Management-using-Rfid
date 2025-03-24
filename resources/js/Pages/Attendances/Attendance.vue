<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useToast } from 'vue-toast-notification';

const attendance = ref([]);
const pagination = ref({});
const toast = useToast();
const isLoading = ref(false);
let interval = null;
const currentUrl = ref('/api/attendance/employees');
const filter_type = ref('all');

// Fetch attendance data
const fetchAttendance = async (url = null) => {
    if (url) {
        currentUrl.value = url;
    }
    
    try {
        isLoading.value = true;
        const response = await axios.get(currentUrl.value);
        attendance.value = response.data.data;
        pagination.value = response.data;
    } catch (e) {
        console.error('Failed to fetch attendance data:', e);
        toast.error('Failed to fetch attendance data');
    } finally {
        isLoading.value = false;
    }
};

// Navigate to another page
const goToPage = (url) => {
    if (url) {
        fetchAttendance(url);
    }
};

const filteredAttendance = computed(() => {
    return attendance.value.filter((record) => {
        const hour = new Date(record.created_at).getHours();
        if (filter_type.value === 'morning') {
            return hour < 12;
        } else if (filter_type.value === 'afternoon') {
            return hour >= 12;
        }
        return true;
    });
});

// Polling function to refresh attendance data every 5 seconds
const pollAttendance = () => {
    fetchAttendance();
};

const getStatusClass = (status) => {
    switch(status?.toLowerCase()) {
        case 'present':
            return 'bg-green-100 text-green-800';
        case 'late':
            return 'bg-yellow-100 text-yellow-800';
        case 'absent':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getTypeClass = (type) => {
    switch(type?.toLowerCase()) {
        case 'check-in':
            return 'bg-blue-100 text-blue-800';
        case 'check-out':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString();
};

onMounted(() => {
    fetchAttendance();
    interval = setInterval(pollAttendance, 1000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-8 max-w-7xl mx-auto sm:px-4">
            <div class="p-4 bg-white shadow-md rounded-lg">
                <!-- Header with filter -->
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-semibold">Real-Time Attendance</h2>
                    <select v-model="filter_type" class="border p-1 rounded">
                        <option value="all">All</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                    </select>
                </div>

                <!-- Table -->
                <table class="w-full border-collapse border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">#</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Date & Time</th>
                            <th class="border p-2">Morning In</th>
                            <th class="border p-2">Lunch <Output></Output></th>
                            <th class="border p-2">Afternoon In</th>
                            <th class="border p-2">Afternoon Out</th>
                            <th class="border p-2">Evening In</th>
                            <th class="border p-2">Evening Out</th>
                            <th class="border p-2">Status</th>

                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(attend, index) in filteredAttendance" :key="attend.id" class="hover:bg-gray-50 text-center">
                            <td class="border p-2">{{ pagination.from ? pagination.from + index : index + 1 }}</td>
                            <td class="border p-2">{{ attend.employee?.first_name ?? 'N/A' }} {{ attend.employee?.last_name ?? 'N/A' }}</td>
                            <td class="border p-2">{{ formatDate(attend.created_at) }}</td>
                            <!-- <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.scan_type)">
                                    {{ attend.scan_type ?? 'N/A' }}
                                </span>
                            </td> -->
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.morning_in)">
                                    {{ attend.morning_in ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.lunch_out)">
                                    {{ attend.lunch_out ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.afternoon_in)">
                                    {{ attend.afternoon_in ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.afternoon_out)">
                                    {{ attend.afternoon_out?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.evening_in)">
                                    {{ attend.evening_in?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getTypeClass(attend.evening_out)">
                                    {{ attend.evening_out?? 'N/A' }}
                                </span>
                            </td>
                            <td class="border p-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(attend.status)">
                                    {{ attend.status ?? 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="filteredAttendance.length === 0">
                            <td class="border p-2 text-center py-8 text-gray-500" colspan="5">No attendance records found.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="pagination.links?.length > 3" class="mt-4 flex justify-center space-x-1 text-sm">
                    <button 
                        v-for="link in pagination.links" 
                        :key="link.label" 
                        @click="goToPage(link.url)" 
                        :disabled="!link.url"
                        class="px-2 py-1 border rounded" 
                        :class="{'bg-blue-500 text-white': link.active, 'text-gray-700': !link.active}">
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
