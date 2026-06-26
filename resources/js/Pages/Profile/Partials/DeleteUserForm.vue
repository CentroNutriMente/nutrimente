<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Elimina account
        </template>

        <template #description>
            Elimina definitivamente il tuo account.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Una volta eliminato l'account, tutte le risorse e i dati associati verranno cancellati definitivamente. Prima di procedere, scarica eventuali dati o informazioni che desideri conservare.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion">
                    Elimina account
                </DangerButton>
            </div>

            <!-- Modale di conferma eliminazione account -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    Elimina account
                </template>

                <template #content>
                    Sei sicuro di voler eliminare il tuo account? Una volta eliminato, tutte le risorse e i dati associati verranno cancellati definitivamente. Inserisci la password per confermare l'eliminazione definitiva del tuo account.

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Password"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        Annulla
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Elimina account
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
