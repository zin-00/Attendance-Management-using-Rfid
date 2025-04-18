<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useToast } from 'vue-toast-notification';
import Modal from '@/Components/Modal.vue';

const toast = useToast();
const isLoading = ref(false);
const filter_type = ref('all');
const attendance = ref([]);
const pagination = ref({});
const ModalState = ref(false);

const props = defineProps({
    attendance: {
        type: Object,
        default: () => ({ data: [] })
    }
});

attendance.value = props.attendance.data;
pagination.value = {
    links: props.attendance.links || [],
    from: props.attendance.from || 0
};

const RealtimeListener = () => {
    window.Echo.channel('attendance')
    .listen('attendance.event', (e) => {
        attendance.value.unshift(e.attendance);
        console.log("Attendance: ", attendance.value)
        
        // If we have more than 50 records, remove the oldest
        if (attendance.value.length > 50) {
            attendance.value.pop();
        }
    });
};

const fetchAttendance = async (url = null) => {
    try {
        isLoading.value = true;
        const response = await axios.get(url || route('attendance.index'));
        attendance.value = response.data.data;
        pagination.value = {
            links: response.data.links,
            from: response.data.from
        };
    } catch (error) {
        toast.error('Failed to fetch attendance data');
    } finally {
        isLoading.value = false;
    }
};

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

const getStatusClass = (status) => {
    switch(status?.toLowerCase()) {
        case 'present': return 'bg-green-100 text-green-800';
        case 'late': return 'bg-yellow-100 text-yellow-800';
        case 'absent': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getTimeClass = (time) => {
    return time ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
};

const formatTime = (time) => {
    if (!time) return 'N/A';

    const [hours, minutes] = time.split(':');
    const date = new Date();
    date.setHours(hours, minutes);

    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
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
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };

    return date.toLocaleString(locale, { ...defaultOptions, ...options });
};



onMounted(() => {
    RealtimeListener();
});

onUnmounted(() => {
    window.Echo.leaveChannel('attendance');
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
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-2">#</th>
                                <th class="border p-2">Name</th>
                                <th class="border p-2">Date</th>
                                <th class="border p-2">Morning In</th>
                                <th class="border p-2">Lunch Out</th>
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
                                <td class="border p-2">{{ formatDate(attend.date) }}</td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.morning_in)">
                                        {{ formatTime(attend.morning_in) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.lunch_out)">
                                        {{ formatTime(attend.lunch_out) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.afternoon_in)">
                                        {{ formatTime(attend.afternoon_in) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.afternoon_out)">
                                        {{ formatTime(attend.afternoon_out) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.evening_in)">
                                        {{ formatTime(attend.evening_in) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getTimeClass(attend.evening_out)">
                                        {{ formatTime(attend.evening_out) }}
                                    </span>
                                </td>
                                <td class="border p-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusClass(attend.status)">
                                        {{ attend.status ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredAttendance.length === 0">
                                <td class="border p-2 text-center py-8 text-gray-500" colspan="10">
                                    No attendance records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.links?.length > 3" class="mt-4 flex justify-center space-x-1 text-sm">
                    <button 
                        v-for="link in pagination.links" 
                        :key="link.label" 
                        @click="goToPage(link.url)" 
                        :disabled="!link.url"
                        class="px-2 py-1 border rounded" 
                        :class="{
                            'bg-blue-500 text-white': link.active, 
                            'text-gray-700 hover:bg-gray-100': !link.active,
                            'cursor-not-allowed opacity-50': !link.url
                        }">
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>