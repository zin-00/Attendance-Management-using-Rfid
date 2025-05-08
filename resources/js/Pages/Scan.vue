<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import 'vue-toast-notification/dist/theme-sugar.css';
import { useToast } from 'vue-toast-notification';
import TextInput from '@/Components/TextInput.vue';
import { FlScanQrCode } from '@kalimahapps/vue-icons';

const props = defineProps({
    lastScan: Object,
});

const toast = useToast();
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const duplicateScan = ref('');
const lastScanTime = ref(null);
const current_time = ref(new Date());
let interval = null;
let echoChannel = null;

const isAutocompleteEnabled = ref(true);

// Initialize with the prop data if available
const lastScanData = ref(props.lastScan || null);

const form = useForm({
    rfid_tag: '',
});

// Update the time
const updateTime = () => {
    current_time.value = new Date();
}

onMounted(() => {
    interval = setInterval(updateTime, 1000);
    setupEventListener();
    focusInput();
});

onUnmounted(() => {
    clearInterval(interval);
    // Clean up Echo listener if it exists
    if (echoChannel) {
        echoChannel.stopListening('.UpdatedAttendance');
    }
});

const focusInput = () => {
    setTimeout(() => {
        const input = document.getElementById('rfid_input');
        if (input) {
            input.focus();
        }
    }, 100);
};

const setupEventListener = () => {
    if (window.Echo) {
        echoChannel = window.Echo.channel('attendance');
        echoChannel.listen('.UpdatedAttendance', (e) => {
            console.log('Event received', e);
            // Update the last scan details if available
            if (e.attendance) {
                lastScanData.value = e.attendance;
                // Make sure we're not in loading state
                isSubmitting.value = false;
            }
        });
    } else {
        console.error('Echo is not available');
    }
};

const submitAttendance = async () => {
    if (!form.rfid_tag || form.rfid_tag.trim() === '') return;
    
    isSubmitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    duplicateScan.value = '';
    
    try {
        const response = await axios.post('/api/attendance/store', {
            rfid_tag: form.rfid_tag,
        });
        
        const message = response.data.message || 'Attendance recorded successfully';
        const scanType = response.data.attendance.scan_type;
        const statusText = response.data.attendance.status;
        
        // Store the last scan information
        lastScanData.value = response.data.attendance;
        
        toast.success(`${message} (${scanType} - ${statusText})`, { position: 'top-right' });
        successMessage.value = message;
        lastScanTime.value = new Date().toLocaleTimeString();
    } catch (e) {
        console.error('Error submitting attendance:', e);
        const message = e.response?.data?.error || 'Failed to submit attendance';
        toast.error(message, { position: 'top-right' });
        errorMessage.value = message;

        if(message.includes('Restricted to scan')){
            duplicateScan.value = message;
        }
    } finally {
        form.reset('rfid_tag');
        isSubmitting.value = false;
        focusInput();
    }
};

// Add a timeout to reset loading state in case of network issues
watch(isSubmitting, (newVal) => {
    if (newVal === true) {
        // Set a timeout to reset the loading state after 5 seconds if it hasn't been reset
        setTimeout(() => {
            if (isSubmitting.value === true) {
                isSubmitting.value = false;
                errorMessage.value = 'Request timed out. Please try again.';
                focusInput();
            }
        }, 5000);
    }
});

// Format the timestamp nicely
const formatTime = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    return `${date.toLocaleDateString()} at ${date.toLocaleTimeString()}`;
};

const getAutocomplete = () => {
    return isAutocompleteEnabled.value ? 'on' : 'off';
};

// Format for clock display
const formatHour = (date) => {
    return date.getHours().toString().padStart(2, '0');
};

const formatMinute = (date) => {
    return date.getMinutes().toString().padStart(2, '0');
};

const formatSecond = (date) => {
    return date.getSeconds().toString().padStart(2, '0');
};

const formatDate = (date) => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(undefined, options);
};
</script>

<template>
    <div class="min-h-screen bg-white flex flex-col">
        <!-- Row 1: Time Display -->
        <div class="w-full bg-black text-white p-6 shadow-md">
            <div class="max-w-6xl mx-auto">
                <div class="flex flex-col items-center justify-center">
                    <!-- Digital Clock Display -->
                    <div class="flex items-center justify-center text-6xl font-bold mb-2">
                        <div>{{ formatHour(current_time) }}</div>
                        <div class="mx-2 animate-pulse">:</div>
                        <div>{{ formatMinute(current_time) }}</div>
                        <div class="mx-2 animate-pulse">:</div>
                        <div>{{ formatSecond(current_time) }}</div>
                    </div>
                    
                    <!-- Date Display -->
                    <div class="text-xl">
                        {{ formatDate(current_time) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Scanner and Recent Scan -->
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Column 1: Scanner Section -->
                <div class="bg-white border border-gray-300 rounded-lg shadow-lg p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-black rounded-lg flex items-center justify-center">
                                <FlScanQrCode class="h-5 w-5 text-white"/>
                            </div>
                            <h1 class="text-2xl font-bold ml-3 text-black">Scanner</h1>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="rfid_input" class="block text-sm font-medium text-gray-700 mb-1">Scan RFID Tag</label>
                        <TextInput
                            id="rfid_input"
                            type="password"
                            placeholder="Scan your RFID"
                            v-model="form.rfid_tag"
                            class="border-1.5 border-gray-300 p-3 rounded-lg w-full text-center text-lg focus:border-gray-600 focus:ring-gray-600 transition-colors"
                            maxlength="10"
                            autofocus
                            @keyup.enter="submitAttendance"
                            :disabled="isSubmitting"
                            autocomplete="off"
                        />
                    </div>
                    
                    <!-- Processing indicator -->
                    <div v-if="isSubmitting" class="flex justify-center items-center mb-4">
                        <svg class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2 text-gray-700">Processing...</span>
                    </div>
                    
                    <!-- Status Messages -->
                    <div v-if="errorMessage" class="bg-gray-100 border border-gray-400 text-gray-800 px-4 py-3 rounded mb-4">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <span>{{ errorMessage }}</span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-6 text-center text-sm text-gray-500">
                        <p>Place your RFID tag near the scanner</p>
                    </div>
                </div>
                
                <!-- Column 2: Recent Scan Information -->
                <div class="bg-white border border-gray-300 rounded-lg shadow-lg p-6">
                    <div class="border-b border-gray-200 pb-4 mb-6">
                        <h2 class="text-2xl font-bold text-black">Recent Scan</h2>
                    </div>
                    
                    <div v-if="lastScanData" class="h-full">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                            <!-- User info with avatar placeholder -->
                            <div class="flex items-center mb-6">
                                <div class="h-16 w-16 bg-black rounded-full flex items-center justify-center text-white text-xl font-bold">
                                    {{ lastScanData.employee?.name ? lastScanData.employee.name.charAt(0) : (lastScanData.user?.name ? lastScanData.user.name.charAt(0) : 'U') }}
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-bold text-black">
                                        {{ lastScanData.employee?.first_name || lastScanData.user?.name || 'Unknown User' }}
                                    </h3>
                                    <div class="flex items-center mt-1">
                                        <span class="text-sm bg-black text-white px-2 py-1 rounded mr-2">{{ lastScanData.scan_type }}</span>
                                        <span class="text-sm text-gray-600">{{ lastScanData.status }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Scan details -->
                            <div class="space-y-4" v-if="lastScanData.employee && lastScanData.employee.position">
                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                    <span class="font-medium text-gray-700">Position</span>
                                    <span class="text-black font-mono">
                                        {{ lastScanData.employee.position.name }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                    <span class="font-medium text-gray-700">Time</span>
                                    <span class="text-black">{{ formatTime(lastScanData.created_at) }}</span>
                                </div>
                            </div>
                            
                            <div class="space-y-4" v-else>
                                <div class="flex justify-between border-b border-gray-200 pb-2">
                                    <span class="font-medium text-gray-700">Time</span>
                                    <span class="text-black">{{ formatTime(lastScanData.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="h-full flex flex-col items-center justify-center text-gray-400 py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="text-lg font-medium">No recent scans</p>
                        <p class="text-sm mt-2">Scan an RFID tag to see details here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>