<template>
    <main
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-white to-emerald-100 px-4">

        <form
            class="w-full max-w-96 bg-white/70 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl px-8 py-10">

            <!-- Logo -->
            <router-link to="/" class="flex justify-center mb-4">
                <ion-icon name="cash-outline"
                    class="w-10 h-10 text-teal-400 hover:scale-110 transition cursor-pointer"></ion-icon>
            </router-link>


            <h2 class="text-4xl font-semibold text-gray-800 text-center">
                Sign in
            </h2>

            <p class="mt-4 text-base text-gray-500 text-center">
                Please enter email and password to access.
            </p>

            <!-- EMAIL -->
            <div class="mt-10">
                <label class="font-medium text-gray-700">Email</label>

                <div class="relative mt-2 group">
                    <ion-icon name="mail-outline"
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-emerald-600 transition"></ion-icon>

                    <input placeholder="Please enter your email"
                        class="rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-400 outline-none px-3 py-3 pl-10 w-full transition"
                        required type="email" name="email" />
                </div>
            </div>

            <!-- PASSWORD -->
            <div class="mt-6">
                <label class="font-medium text-gray-700">Password</label>

                <div class="relative mt-2 group">
                    <ion-icon name="lock-closed-outline"
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg group-focus-within:text-emerald-600 transition"></ion-icon>

                    <input placeholder="Please enter your password"
                        class="rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-400 outline-none px-3 py-3 pl-10 w-full transition"
                        required type="password" name="password" />
                </div>
            </div>

            <button type="submit"
                class="mt-8 py-3 w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-medium transition hover:scale-[1.02] hover:shadow-lg">
                Login
            </button>

            <p class="text-center py-8 text-gray-500">
                Don't have an account?
                <a href="/signup" class="text-emerald-600 hover:underline font-medium">
                    Sign up
                </a>
            </p>

        </form>

    </main>
</template>
