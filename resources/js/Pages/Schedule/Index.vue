<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, onMounted, computed, onUnmounted} from 'vue';
import { useToast } from 'vue-toast-notification';
import { PencilIcon, TrashIcon, AdjustmentsHorizontalIcon, CheckIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useForm } from '@inertiajs/vue3';

const toast = useToast();
const isLoading = ref(false);
const selectedSchedule = ref(null);
const modalState = ref(false);
const isMobileView = ref(false);
const mobileDetailId = ref(null);
const message = ref('');

const props = defineProps({
    schedules: Array
});
const schedules = ref([...props.schedules]);

const isEditMode = ref(false);
const editingScheduleId = ref(null);

const checkMobileView = () => {
    isMobileView.value = window.innerWidth < 768;
};

const toggleMobileDetail = (id) => {
    mobileDetailId.value = mobileDetailId.value === id ? null : id;
};

const openModal = () => {
    form.reset();
    modalState.value = true;
}

const openEditModal = (schedule) => {
    form.name = schedule.name ?? '';
    form.morning_in = schedule.morning_in ?? '';
    form.morning_out = schedule.morning_out ?? '';
    form.afternoon_in = schedule.afternoon_in ?? '';
    form.afternoon_out = schedule.afternoon_out ?? '';
    form.scan_allowance_minutes = String(schedule.scan_allowance_minutes ?? '');
    form.late_minutes = String(schedule.late_minutes ?? '');
    form.isSet = schedule.isSet ?? false;

    isEditMode.value = true;
    editingScheduleId.value = schedule.id;
    modalState.value = true;
}

const closeModal = () => {
    modalState.value = false;
    isEditMode.value = false;
    editingScheduleId.value = null;
    form.reset();
    form.clearErrors();
}

const form = useForm({
    name: '',
    morning_in: '',
    morning_out: '',
    afternoon_in: '',
    afternoon_out: '',
    scan_allowance_minutes: '',
    late_minutes: '',
    isSet: false,
});

const submit = async () => {
    isLoading.value = true;
    try {
        let response;
        if(isEditMode.value){
            response = await axios.put(`/schedule-update/${editingScheduleId.value}`, form.data());
            message.value = response.data.success;
            toast.success(message.value || "Updated successfully.",{ position: 'top-right', duration:3000 });
        } else {
            response = await axios.post(`/schedule-store`, form.data());
            message.value = response.data.success;
            toast.success(message.value || "Added successfully", { position: 'top-right', duration:3000 });
        }
        schedules.value = response.data.schedules;
        closeModal();
    } catch (error) {
        toast.error(error.response.data.message || "Failed to submit.");
    } finally {
        isLoading.value = false;
    }
};

const deleteSchedule = async(id) => {
    if (!confirm('Are you sure you want to delete this schedule?')) return;
    
    try {
        const response = await axios.delete(`/schedule-delete/${id}`);
        message.value = response.data.success;
        toast.success(message.value || "Deleted successfully", { position: 'top-right' });
        schedules.value = response.data.schedules;
        if (selectedSchedule.value === id) {
            selectedSchedule.value = null;
        }
    } catch (error) {
        toast.error("Failed to delete.");
    }
};

const toggleScheduleSelection = (id) => {
    selectedSchedule.value = selectedSchedule.value === id ? null : id;
};

const activateSchedule = async() => {
    if (!selectedSchedule.value) {
        toast.warning('Please select a schedule to activate', { position: 'top-right', duration: 3000 });
        return;
    }

    try {
        const response = await axios.put(`/schedule-update-status/${selectedSchedule.value}`);
        message.value = response.data.message;
        toast.success(message.value, { position: 'top-right', duration: 5000 });
        schedules.value = response.data.schedules;
    } catch (error) {
        toast.error('Error activating schedule');
    }
};

onMounted(() => {
    checkMobileView();
    window.addEventListener('resize', checkMobileView);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobileView);
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 md:py-8 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-4 bg-white shadow-md rounded-lg">
                <!-- Header-->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
                    <h2 class="text-lg font-semibold">Schedules</h2>
                    <div class="flex flex-wrap gap-2">
                        <SecondaryButton @click="openModal" class="w-full sm:w-auto">Add Schedule</SecondaryButton>
                        <PrimaryButton 
                            @click="activateSchedule" 
                            :disabled="!selectedSchedule"
                            class="w-full sm:w-auto flex items-center gap-1"
                        >
                            <CheckIcon class="h-4 w-4" />
                            <span>Activate Selected</span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Desktop View Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full border-collapse border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border p-2 w-10"></th>
                                <th class="border p-2">ID</th>
                                <th class="border p-2">Name</th>
                                <th class="border p-2">Morning In</th>
                                <th class="border p-2">Morning Out</th>
                                <th class="border p-2">Afternoon In</th>
                                <th class="border p-2">Afternoon Out</th>
                                <th class="border p-2">Allowance</th>
                                <th class="border p-2">Late</th>
                                <th class="border p-2">Status</th>
                                <th class="border p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading" class="text-center">
                                <td colspan="11" class="py-4">Loading schedules...</td>
                            </tr>
                            <tr v-else-if="schedules.length === 0" class="text-center">
                                <td colspan="11" class="py-4">No schedules found.</td>
                            </tr>
                            <tr v-for="schedule in schedules" :key="schedule.id" 
                                :class="{'bg-green-50': schedule.isSet, 'hover:bg-gray-50': !schedule.isSet}">
                                <td class="border p-2">
                                    <div class="flex items-center justify-center">
                                        <Checkbox 
                                            :checked="selectedSchedule === schedule.id"
                                            @change="toggleScheduleSelection(schedule.id)"
                                        />
                                    </div>
                                </td>
                                <td class="border p-2">{{ schedule.id }}</td>
                                <td class="border p-2 font-medium">{{ schedule.name }}</td>
                                <td class="border p-2">{{ schedule.morning_in }}</td>
                                <td class="border p-2">{{ schedule.morning_out }}</td>
                                <td class="border p-2">{{ schedule.afternoon_in }}</td>
                                <td class="border p-2">{{ schedule.afternoon_out }}</td>
                                <td class="border p-2">{{ schedule.scan_allowance_minutes }} mins</td>
                                <td class="border p-2">{{ schedule.late_minutes }} mins</td>
                                <td class="border p-2">
                                    <span v-if="schedule.isSet" class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Active</span>
                                    <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Inactive</span>
                                </td>
                                <td class="border p-2">
                                    <div class="flex justify-center space-x-2">
                                        <button @click="openEditModal(schedule)" class="text-gray-700 focus:outline-none hover:text-blue-600">
                                            <PencilIcon class="h-4 w-4"/>
                                        </button>
                                        <button @click="deleteSchedule(schedule.id)" class="text-gray-700 focus:outline-none hover:text-red-600">
                                            <TrashIcon class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View Cards -->
                <div class="md:hidden">
                    <div v-if="isLoading" class="text-center py-4">
                        Loading schedules...
                    </div>
                    <div v-else-if="schedules.length === 0" class="text-center py-4">
                        No schedules found.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="schedule in schedules" :key="schedule.id" 
                            :class="{'border-green-200 bg-green-50': schedule.isSet, 'border-gray-200': !schedule.isSet}"
                            class="border rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between p-3" :class="{'bg-green-100': schedule.isSet, 'bg-gray-50': !schedule.isSet}">
                                <div class="flex items-center space-x-3">
                                    <Checkbox 
                                        :checked="selectedSchedule === schedule.id"
                                        @change="toggleScheduleSelection(schedule.id)"
                                    />
                                    <span class="font-medium truncate">{{ schedule.name }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span v-if="schedule.isSet" class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Active</span>
                                    <button @click="toggleMobileDetail(schedule.id)" class="text-gray-700 p-1 rounded-full hover:bg-gray-200">
                                        <AdjustmentsHorizontalIcon class="h-5 w-5" />
                                    </button>
                                    <button @click="openEditModal(schedule)" class="text-gray-700 p-1 rounded-full hover:bg-gray-200">
                                        <PencilIcon class="h-5 w-5" />
                                    </button>
                                    <button @click="deleteSchedule(schedule.id)" class="text-gray-700 p-1 rounded-full hover:bg-gray-200">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                            <div v-if="mobileDetailId === schedule.id" class="p-3 border-t bg-white">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <div class="text-gray-500">ID</div>
                                        <div>{{ schedule.id }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Morning In</div>
                                        <div>{{ schedule.morning_in }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Morning Out</div>
                                        <div>{{ schedule.morning_out }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Afternoon In</div>
                                        <div>{{ schedule.afternoon_in }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Afternoon Out</div>
                                        <div>{{ schedule.afternoon_out }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Allowance Time</div>
                                        <div>{{ schedule.scan_allowance_minutes }} mins</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Late Minutes</div>
                                        <div>{{ schedule.late_minutes }} mins</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Modal :show="modalState" @close="closeModal" maxWidth="lg">
                    <div class="p-4 sm:p-6">
                        <h2 class="text-lg font-bold mb-4">
                            {{ isEditMode ? 'Edit Schedule' : 'Create Schedule' }}</h2>
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <InputLabel for="name" value="Schedule Name" />
                                <TextInput 
                                    id="name" 
                                    type="text" 
                                    autocomplete="off" 
                                    v-model="form.name"
                                    class="mt-1 block w-full" 
                                />
                                <InputError class="mt-2" :message="form.errors.name"/>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <InputLabel for="morning_in" value="Morning In" />
                                    <TextInput 
                                        id="morning_in" 
                                        type="time" 
                                        v-model="form.morning_in"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.morning_in" />
                                </div>
                                
                                <div class="mb-4">
                                    <InputLabel for="morning_out" value="Morning Out" />
                                    <TextInput 
                                        id="morning_out" 
                                        type="time" 
                                        v-model="form.morning_out"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.morning_out" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <InputLabel for="afternoon_in" value="Afternoon In" />
                                    <TextInput 
                                        id="afternoon_in" 
                                        type="time" 
                                        v-model="form.afternoon_in"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.afternoon_in" />
                                </div>
                                
                                <div class="mb-4">
                                    <InputLabel for="afternoon_out" value="Afternoon Out" />
                                    <TextInput 
                                        id="afternoon_out" 
                                        type="time" 
                                        v-model="form.afternoon_out"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.afternoon_out" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <InputLabel for="allowance_time" value="Time Allowance (Minutes)" />
                                    <TextInput 
                                        id="allowance_time" 
                                        type="text" 
                                        min="0"
                                        v-model="form.scan_allowance_minutes"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.scan_allowance_minutes" />
                                </div>
                                <div class="mb-4">
                                    <InputLabel for="late_minutes" value="Late Minutes" />
                                    <TextInput 
                                        id="late_minutes" 
                                        type="text" 
                                        min="0"
                                        v-model="form.late_minutes"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.late_minutes" />
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row justify-end gap-2 mt-6">
                                <SecondaryButton type="button" @click="closeModal" class="w-full sm:w-auto">Cancel</SecondaryButton>
                                <PrimaryButton type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                                    <span v-if="form.processing">Saving...</span>
                                    <span v-else>Save Schedule</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>