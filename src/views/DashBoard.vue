<script setup>
import { onMounted } from "vue"
import Navbar from "@/components/Navbar.vue"

let activeBalanceId = null
let activeAccountName = ""

function openAccountModal(balanceId, accountName) {
    activeBalanceId = balanceId
    activeAccountName = accountName

    document.getElementById("entryAmount").value = ""
    document.getElementById("entryName").value = ""
    document.getElementById("entryDate").value = ""
    document.getElementById("entryDesc").value = ""

    document.getElementById("accountTargetText").innerText =
        "Eintrag wird zu „" + accountName + "“ hinzugefügt"

    document.getElementById("accountModal").classList.remove("hidden")
}

function closeAccountModal() {
    document.getElementById("accountModal").classList.add("hidden")
    activeBalanceId = null
}

function updateBalanceColor(element, value) {
    if (value < 0) {
        element.classList.add("text-red-500")
        element.classList.remove("text-gray-900")
    } else {
        element.classList.remove("text-red-500")
        element.classList.add("text-gray-900")
    }
}

function saveAccountEntry() {
    const amount = parseFloat(document.getElementById("entryAmount").value)

    if (isNaN(amount)) {
        alert("Bitte einen gültigen Betrag eingeben.")
        return
    }

    const balanceEl = document.getElementById(activeBalanceId)

    let current = balanceEl.innerText
        .replace("€", "")
        .replace(".", "")
        .replace(",", ".")
        .replace("-", "")
        .trim()

    current = parseFloat(current) || 0

    const newTotal = current + amount
    balanceEl.innerText = "€" + newTotal.toLocaleString("de-DE") + ",-"

    updateBalanceColor(balanceEl, newTotal)

    closeAccountModal()
}

function openAddModal() {
    document.getElementById("bankType").value = ""
    document.getElementById("accountName").value = ""
    document.getElementById("accountOwner").value = ""
    document.getElementById("accountBalance").value = ""

    document.getElementById("addAccountModal").classList.remove("hidden")
}

function closeAddModal() {
    document.getElementById("addAccountModal").classList.add("hidden")
}

function saveNewAccount() {
    const bank = document.getElementById("bankType").value
    const name = document.getElementById("accountName").value.trim()
    const owner = document.getElementById("accountOwner").value.trim()
    const balance = parseFloat(document.getElementById("accountBalance").value)

    if (!bank || !name || !owner || isNaN(balance)) {
        alert("Bitte alle Felder korrekt ausfüllen.")
        return
    }

    let iconSrc = ""
    if (bank === "mastercard") iconSrc = "/img/mc_symbol.svg"
    if (bank === "paypal") iconSrc = "/img/paypal_icon.svg"
    if (bank === "revolut") iconSrc = "/img/revolut_icon.svg"

    const id = "balance-" + crypto.randomUUID()

    const html = `
  <section class="pb-5">
      <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">
          <div>
              <div class="flex items-center gap-3 mb-2">
                  <img src="${iconSrc}" class="h-8 w-auto" />
                  <h2 class="text-xl md:text-2xl font-semibold">${name}</h2>
                  <ion-icon name="add-outline"
                      class="ml-auto h-6 w-6 cursor-pointer transition-transform duration-200 ease-out hover:scale-125 active:scale-95"
                      onclick="openAccountModal('${id}', '${name}')">
                  </ion-icon>
              </div>

              <p class="text-gray-700">von ${owner}</p>
              <hr class="my-4 border-gray-400" />
              <p class="text-lg text-gray-800">Gesamtausgaben</p>
              <p id="${id}" class="mt-1 text-3xl md:text-4xl font-semibold">
                  €${balance.toLocaleString("de-DE")},-
              </p>
          </div>
      </div>
  </section>
  `

    document.querySelector("main").insertAdjacentHTML("beforeend", html)

    closeAddModal()
}

onMounted(() => {
    window.openAccountModal = openAccountModal
    window.closeAccountModal = closeAccountModal
    window.saveAccountEntry = saveAccountEntry
    window.openAddModal = openAddModal
    window.closeAddModal = closeAddModal
    window.saveNewAccount = saveNewAccount

    const subCategories = [
        "Lebensmittel",
        "Restaurant",
        "Transport",
        "Freizeit",
        "Abos"
    ]

    const select = document.getElementById("entrySubCategory")
    if (!select) return

    select.innerHTML = '<option value="">Bitte wählen</option>'

    subCategories.forEach(sub => {
        const option = document.createElement("option")
        option.value = sub.toLowerCase()
        option.textContent = sub
        select.appendChild(option)
    })
})
</script>

<template>
    <div class="flex text-gray-900">
        <Navbar />

        <main class="flex-1 min-w-0 p-6">
            <h1 class="text-3xl font-semibold mb-6 ">Dashboard</h1>
            <!-- Top bar (just the page label to mimic mockup) -->

            <section class="pb-5">
                <!-- Top summary card from mockup -->
                <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

                    <!-- Texts -->
                    <div>
                        <!-- Headline with icon -->
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center">
                                <img src="/img/mc_symbol.svg" class="max-w-full max-h-full object-contain" />
                            </div>
                            <h2 class="text-xl md:text-2xl font-semibold">
                                Girokonto #9237
                            </h2>
                            <ion-icon name="add-outline"
                                class="ml-auto h-6 w-6 cursor-pointer transition-transform duration-200 ease-out hover:scale-125 active:scale-95"
                                onclick="openAccountModal('balance-1', 'Girokonto #9237')">
                            </ion-icon>


                        </div>

                        <p class="text-gray-700">von Max Mustermann</p>
                        <hr class="my-4 border-gray-400" />
                        <p class="text-lg md:text-xl text-gray-800">Gesamtausgaben</p>
                        <p id="balance-1" class="mt-1 text-3xl md:text-4xl font-semibold">€9.800,-</p>
                    </div>
                </div>
            </section>

            <section class="pb-5">
                <!-- Top summary card from mockup -->
                <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

                    <!-- Texts -->
                    <div>
                        <!-- Headline with icon -->
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center">
                                <img src="/img/revolut_icon.svg" class="max-w-full max-h-full object-contain" />
                            </div>
                            <h2 class="text-xl md:text-2xl font-semibold">
                                Revolut #2895
                            </h2>
                            <ion-icon name="add-outline"
                                class="ml-auto h-6 w-6 cursor-pointer transition-transform duration-200 ease-out hover:scale-125 active:scale-95"
                                onclick="openAccountModal('balance-2', 'Revolut #2895')">
                            </ion-icon>


                        </div>

                        <p class="text-gray-700">von Yousef Sheikho</p>
                        <hr class="my-4 border-gray-400" />
                        <p class="text-lg md:text-xl text-gray-800">Gesamtausgaben</p>
                        <p id="balance-2" class="mt-1 text-3xl md:text-4xl font-semibold">€553,-</p>
                    </div>
                </div>
            </section>

            <section class="pb-5">
                <!-- Top summary card from mockup -->
                <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

                    <!-- Texts -->
                    <div>
                        <!-- Headline with icon -->
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center">
                                <img src="/img/paypal_icon.svg" class="max-w-full max-h-full object-contain" />
                            </div>

                            <h2 class="text-xl md:text-2xl font-semibold">
                                PayPal #2895
                            </h2>
                            <ion-icon name="add-outline"
                                class="ml-auto h-6 w-6 cursor-pointer transition-transform duration-200 ease-out hover:scale-125 active:scale-95"
                                onclick="openAccountModal('balance-3', 'PayPal #2895')">
                            </ion-icon>


                        </div>

                        <p class="text-gray-700">von Max Mustermann</p>
                        <hr class="my-4 border-gray-400" />
                        <p class="text-lg md:text-xl text-gray-800">Gesamtausgaben</p>
                        <p id="balance-3" class="mt-1 text-3xl md:text-4xl font-semibold">€1.198,-</p>
                    </div>
                </div>
            </section>

        </main>

        <!-- Deine beiden Modals ebenfalls hier 1:1 einfügen -->
    </div>

    <!-- Floating Add Button -->
    <button onclick="openAddModal()"
        class="fixed bottom-6 right-6 w-16 h-16 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition transform hover:scale-110">
        <ion-icon name="add-outline"></ion-icon>
    </button>

    <!-- MODAL -->
    <div id="accountModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
        <div class="bg-white w-11/12 max-w-lg mx-auto mt-32 p-8 rounded-2xl shadow-xl">

            <h2 class="text-2xl font-semibold mb-2">Neuen Eintrag hinzufügen</h2>
            <p id="accountTargetText" class="text-sm text-gray-500 mb-6">
                <!-- wird per JS gesetzt -->
            </p>

            <!-- Betrag -->
            <label class="block text-sm mb-1">Betrag (€)</label>
            <input id="entryAmount" type="number" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <!-- Name -->
            <label class="block text-sm mb-1">Name</label>
            <input id="entryName" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <!-- Datum -->
            <label class="block text-sm mb-1">Datum</label>
            <input id="entryDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <!-- Beschreibung -->
            <label class="block text-sm mb-1">Beschreibung</label>
            <textarea id="entryDesc" class="w-full px-3 py-2 border border-gray-300 rounded-xl"></textarea>

            <!-- Sub Kategorie -->
            <label class="block text-sm mb-1 mt-3">Unterkategorie wählen</label>
            <select id="entrySubCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></select>

            <!-- Buttons -->
            <div class="flex justify-end gap-4 mt-6">
                <button onclick="closeAccountModal()" class="px-4 py-2 rounded-lg border border-gray-300">
                    Abbrechen
                </button>
                <button onclick="saveAccountEntry()"
                    class="px-4 py-2 rounded-lg bg-rose-500 text-white hover:bg-rose-600">
                    Speichern
                </button>
            </div>
        </div>
    </div>

    <!-- ADD ACCOUNT MODAL -->
    <div id="addAccountModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
        <div class="bg-white w-11/12 max-w-lg mx-auto mt-32 p-8 rounded-2xl shadow-xl">

            <h2 class="text-2xl font-semibold mb-6">Neues Bankkonto hinzufügen</h2>

            <!-- Bank auswählen -->
            <label class="block text-sm mb-1">Bank / Konto</label>
            <select id="bankType" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
                <option value="">Bitte wählen</option>
                <option value="mastercard">Mastercard</option>
                <option value="paypal">PayPal</option>
                <option value="revolut">Revolut</option>
            </select>

            <!-- Kontoname -->
            <label class="block text-sm mb-1">Kontoname / Nummer</label>
            <input id="accountName" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4"
                placeholder="z.B. Girokonto #1234" />

            <!-- Kontoinhaber -->
            <label class="block text-sm mb-1">Kontoinhaber</label>
            <input id="accountOwner" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4"
                placeholder="Max Mustermann" />

            <!-- Startbetrag -->
            <label class="block text-sm mb-1">Aktueller Kontostand (€)</label>
            <input id="accountBalance" type="number" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

            <!-- Buttons -->
            <div class="flex justify-end gap-4">
                <button onclick="closeAddModal()" class="px-4 py-2 rounded-lg border border-gray-300">
                    Abbrechen
                </button>
                <button onclick="saveNewAccount()"
                    class="px-4 py-2 rounded-lg bg-teal-400 text-white hover:bg-teal-500">
                    Hinzufügen
                </button>
            </div>

        </div>
    </div>
</template>
