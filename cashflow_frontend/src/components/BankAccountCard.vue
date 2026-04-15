<script setup>
const props = defineProps({
    accountName: String,
    accountOwner: String,
    icon: String,
    balance: String,
    balanceId: String,
    iban: String,
})

const emit = defineEmits(["add-entry", "edit-bank"])

function handleAddClick() {
    emit("add-entry", props.balanceId, props.accountName)
}
</script>

<template>
    <section class="pb-5">
        <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

            <!-- HEADER -->
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center">
                    <img :src="icon" class="max-w-full max-h-full object-contain" />
                </div>

                <div>
                    <h2 class="text-xl md:text-2xl font-semibold">
                        {{ accountName }}
                    </h2>

                    <p v-if="iban" class="text-sm text-gray-500">
                        {{ iban }}
                    </p>
                </div>

                <!-- RECHTS -->
                <div class="ml-auto flex items-center gap-4">
                    <ion-icon name="pencil" class="h-6 w-6 cursor-pointer hover:scale-125 active:scale-95"
                        @click="$emit('edit-bank')" />

                    <ion-icon name="add-outline" class="h-6 w-6 cursor-pointer hover:scale-125 active:scale-95"
                        @click="handleAddClick" />
                </div>
            </div>

            <hr class="my-4 border-gray-400" />

            <p class="text-lg md:text-xl text-gray-800">Gesamtausgaben</p>

            <p :id="balanceId" class="mt-1 text-3xl md:text-4xl font-semibold">
                {{ balance }}
            </p>
        </div>
    </section>

</template>