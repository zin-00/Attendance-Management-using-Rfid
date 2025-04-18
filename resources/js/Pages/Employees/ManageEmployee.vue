<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'vue-toast-notification';
import { Country, State, City } from 'country-state-city';

const props = defineProps({ 
    positions: Array,
    employee: Object,
});

const $toast = useToast();
const page = usePage();
const isLoading = ref(false);
const formError = ref('');
const rfidInputRef = ref(null);
const currentStep = ref(1);
const totalSteps = 3;
const showSuccessNotification = ref(false);
const successMessage = ref('');

const countries = ref([]);
const states = ref([]);
const cities = ref([]);
const selectedCountry = ref('');
const selectedState = ref('');

// Form state that handle both update and store
const form = useForm({
    rfid_tag: props.employee?.rfid_tag || '',
    first_name: props.employee?.first_name || '',
    last_name: props.employee?.last_name || '',
    birthdate: props.employee?.birthdate || '',
    contact_number: props.employee?.contact_number || '',
    emergency_contact: props.employee?.emergency_contact || '',
    emergency_contact_number: props.employee?.emergency_contact_number || '',
    street_address: props.employee?.street_address || '',
    country: props.employee?.country || '',
    city: props.employee?.city || '',
    state: props.employee?.state || '',
    zip_code: props.employee?.zip_code || '',
    hire_date: props.employee?.hire_date || new Date().toISOString().split('T')[0],
    email: props.employee?.email || '',
    // password: '',
    // password_confirmation: '',
    gender: props.employee?.gender || '',
    status: props.employee?.status || 'Active',
    position_id: props.employee?.position_id || '',
    profile_image: null,
});

// Initialize country, state, city if employee exists
if (props.employee) {
    selectedCountry.value = props.employee.country;
    selectedState.value = props.employee.state;
    // Load the states and cities based on the selected country and state
    if (selectedCountry.value) {
        states.value = State.getStatesOfCountry(selectedCountry.value);
        if (selectedState.value) {
            cities.value = City.getCitiesOfState(selectedCountry.value, selectedState.value);
        }
    }
}

// const passwordStrength = computed(() => {
//     if (!form.password) return 0;
//     let score = 0;
//     if (form.password.length >= 8) score++;
//     if (/[A-Z]/.test(form.password)) score++;
//     if (/[a-z]/.test(form.password)) score++;
//     if (/[0-9]/.test(form.password)) score++;
//     if (/[^A-Za-z0-9]/.test(form.password)) score++;
//     return score;
// });

// const passwordStrengthColor = computed(() => {
//     return ['bg-red-500', 'bg-yellow-500', 'bg-green-500'][Math.min(passwordStrength.value - 1, 2)];
// });

// const passwordStrengthWidth = computed(() => `${(passwordStrength.value / 5) * 100}%`);

const emailIsValid = computed(() => {
    return !form.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email);
});

const formatPhoneNumber = (event) => {
    let value = event.target.value.replace(/\D/g, '');
    form.contact_number = value.length > 6
        ? `${value.slice(0, 3)}-${value.slice(3, 6)}-${value.slice(6, 10)}`
        : value.length > 3
            ? `${value.slice(0, 3)}-${value.slice(3)}`
            : value;
};

const formatEmergencyPhoneNumber = (event) => {
    let value = event.target.value.replace(/\D/g, '');
    form.emergency_contact_number = value.length > 6
        ? `${value.slice(0, 3)}-${value.slice(3, 6)}-${value.slice(6, 10)}`
        : value.length > 3
            ? `${value.slice(0, 3)}-${value.slice(3)}`
            : value;
};

// Handle profile image upload
const handleProfileImageUpload = (event) => {
    form.profile_image = event.target.files[0];
};

// Focus on RFID input
const focusOnRfid = () => {
    nextTick(() => rfidInputRef.value?.focus());
};

// Step navigation
const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
        window.scrollTo(0, 0);
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo(0, 0);
    }
};

// Validate form step before proceeding
const currentStepIsValid = computed(() => {
    if (currentStep.value === 1) {
        return form.rfid_tag && form.first_name && form.last_name && form.birthdate;
    } else if (currentStep.value === 2) {
        return form.email && emailIsValid.value;
    }
    return true;
});

// Reset form
const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.profile_image = null;
    form.hire_date = new Date().toISOString().split('T')[0];
    form.status = 'Active';
    form.gender = '';
    form.position_id = '';
    currentStep.value = 1;  
    formError.value = '';
    selectedCountry.value = '';
    selectedState.value = '';
    states.value = [];
    cities.value = [];
    
    nextTick(() => {
        console.log("Form fully reset!");
        focusOnRfid();
    });
};

// Function for fetching country, state and city
const fetchStates = () => {
   if(selectedCountry.value){ 
    states.value = State.getStatesOfCountry(selectedCountry.value);
    selectedState.value = ''; // Reset state when country changes
    cities.value = []; // Reset cities when country changes
    form.country = selectedCountry.value;
    form.state = '';
    form.city = '';}
};

const fetchCities = () => {
   if(selectedCountry.value && selectedState.value){  
    cities.value = City.getCitiesOfState(selectedCountry.value, selectedState.value);
    form.state = selectedState.value;
    form.city = '';
    }
};

watch(selectedCountry, (newCountry) =>{
    if(newCountry){
        form.country = newCountry.value;
        fetchStates();
    }
});
watch(selectedState, (newState) =>{
    if(newState){
        form.state = newState.value;
        fetchCities();
    }
});

onMounted(() => {
    countries.value = Country.getAllCountries();
    focusOnRfid();
});

// Watch for city selection to update form
watch(() => form.city, (newCity) => {
    if (newCity) {
        // Ensure the city value is saved in the form
        form.city = newCity;
    }
});

// Submit form
const submit = () => {
    isLoading.value = true;
    formError.value = '';

    if (props.employee) {
        form.put(route('employee.update', props.employee.id), {
            preserveState: false,
            preserveScroll: true,
            onStart: () => isLoading.value = true,
            onSuccess: () => {
                isLoading.value = false;
                resetForm();
                $toast.success('Employee has been updated', {position: 'top-right'});
                successMessage.value = 'Employee has been updated';
                showSuccessNotification.value = true;
                setTimeout(() => {
                    showSuccessNotification.value = false;
                }, 5000);
            },
            onError: (errors) => {
                isLoading.value = false;
                formError.value = 'Failed to submit the form. Please check all fields and try again.';
                console.error("Form submission errors:", errors);
                $toast.error('Error submitting', {position: 'top-right'});
                setTimeout(() => {
                    formError.value = '';
                }, 5000);
            },
            onFinish: () => isLoading.value = false
        });
    } else {
        form.post(route('employee.store'), {
            preserveState: false,
            preserveScroll: true,
            onStart: () => isLoading.value = true,
            onSuccess: () => {
                isLoading.value = false;
                resetForm();
                $toast.success('Employee has been added', {position: 'top-right'});
                successMessage.value = 'Employee has been added';
                showSuccessNotification.value = true;
                setTimeout(() => {
                    showSuccessNotification.value = false;
                }, 5000);
            },
            onError: (errors) => {
                isLoading.value = false;
                formError.value = 'Failed to submit the form. Please check all fields and try again.';
                console.error("Form submission errors:", errors);
                $toast.error('Error submitting', {position: 'top-right'});
                setTimeout(() => {
                    formError.value = '';
                }, 5000);
            },
            onFinish: () => isLoading.value = false
        });
    }
};

watch(() => page.props.flash?.success, (newMessage) => {
    if (newMessage) {
        $toast.success(newMessage, { position: 'top-right' });
        successMessage.value = newMessage;
        showSuccessNotification.value = true;
        resetForm(); 
        setTimeout(() => {
            showSuccessNotification.value = false;
        }, 5000);
    }
});

watch(() => page.props.flash?.error, (newMessage) => {
    if (newMessage) {
        $toast.error(newMessage, { position: 'top-right' });
    }
});
</script>

<template>
    <Head title="Register Employee" />
    
    <AuthenticatedLayout>
        <div class="h-full flex items-center justify-center p-4 bg-gray-50">
            <!-- Success Notification -->
            <div v-if="showSuccessNotification" 
                class="fixed top-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-lg z-50 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ successMessage }}</span>
                <button @click="showSuccessNotification = false" class="ml-4 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="formError" class="text-red-500 text-sm mt-2">{{ formError }}</div>

            <div class="bg-white shadow-lg rounded-lg w-full max-w-5xl">
                <!-- Header with Progress Bar -->
                <div class="bg-gray-50 rounded-t-lg p-4 border-b">
                    <h2 class="text-lg font-semibold">Register Employee</h2>
                    <div class="mt-4 flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
                        </div>
                        <span class="text-sm text-gray-600">Step {{ currentStep }} of {{ totalSteps }}</span>
                    </div>
                </div>
                
                <form @submit.prevent="submit" class="p-4">
                    <!-- Error notification -->
                    <div v-if="formError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-md">
                        {{ formError }}
                    </div>
                    
                    <!-- Step 1: Personal Information -->
                    <div v-if="currentStep === 1">
                        <h3 class="font-medium text-gray-700 mb-4">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">RFID Tag*</label>
                                <TextInput 
                                    ref="rfidInputRef"
                                    v-model="form.rfid_tag" 
                                    type="text" 
                                    class="mt-1 block w-full text-sm" 
                                    placeholder="Scan RFID card"
                                    required 
                                    @focus="$event.target.select()"
                                />
                                <InputError :message="form.errors.rfid_tag" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name*</label>
                                <TextInput v-model="form.first_name" type="text" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.first_name" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name*</label>
                                <TextInput v-model="form.last_name" type="text" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.last_name" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Birthdate*</label>
                                <TextInput v-model="form.birthdate" type="date" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.birthdate" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hire Date*</label>
                                <TextInput v-model="form.hire_date" type="date" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.hire_date" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Profile Image</label>
                                <input 
                                    type="file" 
                                    @change="handleProfileImageUpload" 
                                    accept="image/*"
                                    class="mt-1 block w-full text-sm border border-gray-300 rounded-md p-2" 
                                />
                                <InputError :message="form.errors.profile_image" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Number*</label>
                                <TextInput 
                                    v-model="form.contact_number" 
                                    type="text" 
                                    class="mt-1 block w-full text-sm" 
                                    placeholder="xxx-xxx-xxxx"
                                    @input="formatPhoneNumber"
                                    required 
                                />
                                <InputError :message="form.errors.contact_number" class="mt-1 text-xs" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Emergency Contact</label>
                                <TextInput v-model="form.emergency_contact" type="text" class="mt-1 block w-full text-sm" />
                                <InputError :message="form.errors.emergency_contact" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Emergency Contact Number</label>
                                <TextInput 
                                    v-model="form.emergency_contact_number" 
                                    type="text" 
                                    class="mt-1 block w-full text-sm"
                                    placeholder="xxx-xxx-xxxx"
                                    @input="formatEmergencyPhoneNumber"
                                />
                                <InputError :message="form.errors.emergency_contact_number" class="mt-1 text-xs" />
                            </div>
                            
                            <!-- Address fields -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Street Address*</label>
                                <TextInput v-model="form.street_address" type="text" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.street_address" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country*</label>
                                <select 
                                    v-model="selectedCountry" 
                                    @change="fetchStates" 
                                    class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm" 
                                    required
                                >
                                    <option value="" disabled>Select a country</option>
                                    <option v-for="country in countries" :key="country.isoCode" :value="country.isoCode">
                                        {{ country.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.country" class="mt-1 text-xs" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">State/Province*</label>
                                <select 
                                    v-model="selectedState" 
                                    @change="fetchCities" 
                                    class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm" 
                                    required
                                >
                                    <option value="" disabled>Select a state</option>
                                    <option v-for="state in states" :key="state.isoCode" :value="state.isoCode">
                                        {{ state.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.state" class="mt-1 text-xs" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">City*</label>
                                <select 
                                    v-model="form.city" 
                                    class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm" 
                                    required
                                >
                                    <option value="" disabled>Select a city</option>
                                    <option v-for="city in cities" :key="city.name" :value="city.name">
                                        {{ city.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.city" class="mt-1 text-xs" />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ZIP/Postal Code*</label>
                                <TextInput v-model="form.zip_code" type="text" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.zip_code" class="mt-1 text-xs" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Account Information -->
                    <div v-if="currentStep === 2">
                        <h3 class="font-medium text-gray-700 mb-4">Account Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email*</label>
                                <TextInput v-model="form.email" type="email" class="mt-1 block w-full text-sm" required />
                                <InputError 
                                    :message="!emailIsValid && form.email ? 'Please enter a valid email address' : form.errors.email" 
                                    class="mt-1 text-xs" 
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Position*</label>
                                <select v-model="form.position_id" class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm" required>
                                    <option value="" disabled>Select a position</option>
                                    <option v-for="position in props.positions" :key="position.id" :value="position.id">
                                        {{ position.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.position_id" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status*</label>
                                <select v-model="form.status" class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Resigned">Resigned</option>
                                    <option value="Banned">Banned</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-1 text-xs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender*</label>
                                <select v-model="form.gender" class="mt-1 block w-full border-gray-300 text-sm rounded-md shadow-sm" required>
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError :message="form.errors.gender" class="mt-1 text-xs" />
                            </div>
                            <!-- <div>
                                <label class="block text-sm font-medium text-gray-700">Password*</label>
                                <TextInput v-model="form.password" type="password" class="mt-1 block w-full text-sm" required />
                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                    <div :class="[passwordStrengthColor, 'h-2 rounded-full transition-all duration-300']" 
                                         :style="`width: ${passwordStrengthWidth}`"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Password should include uppercase, lowercase, numbers and special characters
                                </p>
                                <InputError :message="form.errors.password" class="mt-1 text-xs" />
                            </div> -->
                            <!-- <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm Password*</label>
                                <TextInput v-model="form.password_confirmation" type="password" class="mt-1 block w-full text-sm" required />
                                <InputError :message="form.errors.password_confirmation" class="mt-1 text-xs" />
                                <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" 
                                   class="text-xs text-red-500 mt-1">
                                    Passwords do not match
                                </p>
                            </div> -->
                        </div>
                    </div>
                    
                    <!-- Step 3: Review Information -->
                    <div v-if="currentStep === 3">
                        <h3 class="font-medium text-gray-700 mb-4">Review Information</h3>
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <h4 class="font-medium text-sm mb-2">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <div><span class="font-semibold">Name:</span> {{ form.first_name }} {{ form.last_name }}</div>
                                <div><span class="font-semibold">RFID Tag:</span> {{ form.rfid_tag }}</div>
                                <div><span class="font-semibold">Birthdate:</span> {{ form.birthdate }}</div>
                                <div><span class="font-semibold">Contact:</span> {{ form.contact_number }}</div>
                                <div><span class="font-semibold">Email:</span> {{ form.email }}</div>
                                <div><span class="font-semibold">Position:</span> {{ 
                                    props.positions.find(p => p.id == form.position_id)?.name || 'Not selected' 
                                }}</div>
                                <div><span class="font-semibold">Status:</span> {{ form.status }}</div>
                                <div><span class="font-semibold">Gender:</span> {{ form.gender }}</div>
                                <div><span class="font-semibold">Hire Date:</span> {{ form.hire_date }}</div>
                                <div class="md:col-span-2">
                                    <span class="font-semibold">Address:</span> 
                                    {{ form.street_address }}, {{ form.city }}, {{ form.state }} {{ form.zip_code }}
                                </div>
                                <div v-if="form.emergency_contact"><span class="font-semibold">Emergency Contact:</span> {{ form.emergency_contact }}</div>
                                <div v-if="form.emergency_contact_number"><span class="font-semibold">Emergency Number:</span> {{ form.emergency_contact_number }}</div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                            <p class="text-sm text-yellow-700">
                                Please review all information carefully before submitting. Once submitted, the employee will receive 
                                an email with their login credentials.
                            </p>
                        </div>
                    </div>

                    <!-- Button Section -->
                    <div class="flex justify-between mt-6 border-t pt-4">
                        <button 
                            type="button" 
                            @click="prevStep"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md text-sm transition duration-150"
                            :class="{ 'invisible': currentStep === 1 }"
                        >
                            Back
                        </button>
                        
                        <div v-if="currentStep < totalSteps">
                            <button 
                                type="button" 
                                @click="nextStep"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md text-sm transition duration-150"
                                :disabled="!currentStepIsValid"
                                :class="{ 'opacity-50 cursor-not-allowed': !currentStepIsValid }"
                            >
                                Continue
                            </button>
                        </div>
                        
                        <button 
                            v-if="currentStep === totalSteps"
                            type="submit" 
                            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-md text-sm transition duration-150" 
                            :disabled="isLoading"
                        >
                            <span v-if="isLoading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                            <span v-else>Register Employee</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>