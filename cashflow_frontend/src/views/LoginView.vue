<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const email = ref("")
const password = ref("")
const error = ref("")

async function login() {
    error.value = ""

    try {
        const res = await fetch("/cashflow_api/auth/login.php", {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                user: email.value,
                password: password.value
            })
        })

        const data = await res.json()

        if (!res.ok || data.error || !data.userid) {
            error.value = data.error
                || "Login fehlgeschlagen"
            localStorage.removeItem("userid")
            localStorage.removeItem("username")
            return
        }

        // speichern
        localStorage.setItem("userid", String(data.userid))
        localStorage.setItem("username", data.name)

        router.push("/")
    } catch (e) {
        error.value = "Server error"
    }
}
</script>

<template>
    <main
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-emerald-100 px-4">

        <form @submit.prevent="login"
            class="w-full max-w-96 bg-white/70 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl px-8 py-10">

            <router-link to="/" class="flex justify-center mb-4">
                <svg class="w-12 h-12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 424.53 430.47">
                    <g>
                        <path fill="#80c17f"
                            d="M424.48,214.57l-22.04-1.14c-.86-140.05-148.02-233.76-275.47-169.08C5.98,105.76-12.64,274.19,90.49,361.99c121.11,103.1,304.39,18.42,311.94-137.16l22.04-.38c-6.37,148.65-154.47,247.06-294.46,189.22C-16.7,353.06-45.62,152.35,76.06,50.44c140.54-117.72,349.44-15.58,348.42,164.14h0Z" />
                        <g>
                            <path fill="#fffeff"
                                d="M424.48,214.57c-.02,3.27.14,6.61,0,9.88l-22.04.38c-7.55,155.59-190.83,240.26-311.94,137.16C-12.64,274.19,5.99,105.76,126.98,44.35c127.44-64.68,274.61,29.03,275.47,169.08l22.04,1.14h-.01Z" />
                            <path fill="#008d36"
                                d="M208.29,65.63c42.47-3.08,91.33,12.03,118.93,45.21l-22.42,26.22c-53.18-57.02-148.88-45.14-183.52,25.46l174.4,1.14-13.68,31.92-243.55-.38c2.81-4.58,11.03-28.2,13.68-30.4,3.43-2.84,21.8-.22,26.98-1.14,21.57-53.55,70.48-93.77,129.18-98.03h0Z" />
                            <path fill="#e50d36"
                                d="M115.96,251.8c23.54,84.1,133.26,104.01,189.6,39.52l22.42,27.74c-80.26,85.01-226.59,46.49-252.29-68.01,13.39.09,26.94.73,40.27.76h0Z" />
                            <path fill="#e50d36"
                                d="M115.96,251.8c-13.33-.03-26.89-.67-40.27-.76s-26.37.38-39.52-.38l14.82-32.3h221.13l-13.68,32.68c-47.47-.91-95.05.88-142.48.76h0Z" />
                        </g>
                    </g>
                </svg>
            </router-link>

            <h2 class="text-4xl font-semibold text-gray-800 text-center">
                Sign in
            </h2>

            <p class="mt-4 text-base text-gray-500 text-center">
                Please enter email and password to access.
            </p>

            <div class="mt-10">
                <label class="font-medium text-gray-700">Email</label>
                <div class="relative mt-2 group">
                    <ion-icon name="mail-outline"
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></ion-icon>

                    <input v-model="email" placeholder="Please enter your email"
                        class="rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-400 outline-none px-3 py-3 pl-10 w-full transition" />
                </div>
            </div>

            <div class="mt-6">
                <label class="font-medium text-gray-700">Password</label>
                <div class="relative mt-2 group">
                    <ion-icon name="lock-closed-outline"
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></ion-icon>

                    <input v-model="password" placeholder="Please enter your password"
                        class="rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-400 outline-none px-3 py-3 pl-10 w-full transition"
                        required type="password" />
                </div>
            </div>

            <p v-if="error" class="text-red-500 mt-4 text-sm text-center">
                {{ error }}
            </p>

            <button type="submit"
                class="mt-8 py-3 w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-medium transition hover:scale-[1.02] hover:shadow-lg">
                Login
            </button>

        </form>
    </main>
</template>
