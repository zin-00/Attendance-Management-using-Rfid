<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import 'vue-toast-notification/dist/theme-sugar.css';
import { useToast } from 'vue-toast-notification';

const toast = useToast();
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const duplicateScan = ref('');
const lastScanTime = ref(null);
const current_time = ref(new Date());
let interval = null;

const form = useForm({
    rfid_tag: '',
});

// update sa time
const updateTime = () =>{
    current_time.value = new Date();
}

onMounted(() => {
    interval = setInterval(updateTime, 1000);
});
onUnmounted(() => {
    clearInterval(interval);
});
watch(() => form.rfid_tag, async (newValue) => {
    if (newValue.length === 10 && !isSubmitting.value) {
        await submitAttendance();
    }
});

const submitAttendance = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    try {
        const response = await axios.post('/api/attendance/store', {
            rfid_tag: form.rfid_tag,
        });
        
        const message = response.data.message || 'Attendance recorded successfully';
        const scanType = response.data.attendance.scan_type;
        const statusText = response.data.attendance.status;
        
        toast.success(`${message} (${scanType} - ${statusText})`, { position: 'top-right' });
        successMessage.value = message;
        lastScanTime.value = new Date().toLocaleTimeString();
        form.reset('rfid_tag');
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
        setTimeout(() => {
            document.getElementById('rfid_input').focus();
        }, 100);
    }
};
</script>

<template>
    <GuestLayout>
        <div class="max-w-md mx-auto p-6 bg-white rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-6 text-center">Attendance System</h1>
            
            <!-- Current Time Display -->
            <div class="mb-6 text-center">
                <div class="text-xl font-medium">{{ current_time.toLocaleTimeString() }}</div>
                <div class="text-sm text-gray-500">{{ current_time.toLocaleDateString() }}</div>
            </div>
            
            <!-- RFID Input -->
            <div class="flex flex-col gap-4">
                <input
                    id="rfid_input"
                    placeholder="Scan your RFID tag"
                    v-model="form.rfid_tag"
                    class="border p-3 rounded-lg w-full text-center text-lg"
                    maxlength="10"
                    autofocus
                    :disabled="isSubmitting"
                />
                
                <!-- Last Action -->
                <div v-if="lastScanTime" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded text-center">
                    Last scan processed at {{ lastScanTime }}
                </div>
                
                <!-- Status Messages -->
                <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ errorMessage}}
                </div>
                
                <!-- Processing indicator -->
                <div v-if="isSubmitting" class="text-center text-gray-600">
                    Processing...
                </div>
            </div>
        </div>
    </GuestLayout>
</template>