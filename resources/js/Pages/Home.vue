<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { RbacPermission } from '@/constants/rbac';

const brandName = 'Eduvidra';
const year = new Date().getFullYear();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isLoggedIn = computed(() => !!user.value);
const canAdmin = computed(() =>
  (user.value?.permissions ?? []).includes(RbacPermission.ADMIN_ACCESS),
);

const isDark = ref(false);

const applyTheme = (dark) => {
  if (typeof document === 'undefined') return;

  document.documentElement.classList.toggle('dark', dark);
  localStorage.setItem('theme', dark ? 'dark' : 'light');
};

onMounted(() => {
  const userTheme = localStorage.getItem('theme');
  const systemDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false;

  isDark.value = userTheme
    ? userTheme === 'dark'
    : systemDark;

  applyTheme(isDark.value);
});

watch(isDark, (dark) => applyTheme(dark));
</script>

<template>
    <Head title="Home" />

    <div
      class="absolute top-0 left-0 z-40 flex items-center w-full bg-transparent ud-header"
    >
      <div class="container px-4 mx-auto">
        <div class="relative flex items-center justify-between -mx-4">
          <div class="max-w-full px-4 w-60">
            <Link href="/" class="block w-full py-5 navbar-logo">
              <span class="text-2xl font-bold tracking-tight text-white lg:text-white">
                {{ brandName }}
              </span>
            </Link>
          </div>
          <div class="flex items-center justify-between w-full px-4">
            <div>
              <button
                id="navbarToggler"
                class="absolute right-4 top-1/2 block -translate-y-1/2 rounded-lg px-3 py-[6px] ring-primary focus:ring-2 lg:hidden"
              >
                <span
                  class="relative my-[6px] block h-[2px] w-[30px] bg-white"
                ></span>
                <span
                  class="relative my-[6px] block h-[2px] w-[30px] bg-white"
                ></span>
                <span
                  class="relative my-[6px] block h-[2px] w-[30px] bg-white"
                ></span>
              </button>
              <nav
                id="navbarCollapse"
                class="absolute right-4 top-full hidden w-full max-w-[250px] rounded-lg bg-white py-5 shadow-lg dark:bg-dark-2 lg:static lg:block lg:w-full lg:max-w-full lg:bg-transparent lg:px-4 lg:py-0 lg:shadow-none dark:lg:bg-transparent xl:px-6"
              >
                <ul class="blcok lg:flex 2xl:ml-20">
                  <li class="relative group">
                    <a
                      href="#home"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70"
                    >
                      Home
                    </a>
                  </li>
                  <li class="relative group">
                    <a
                      href="#about"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      About
                    </a>
                  </li>
                  <li class="relative group">
                    <a
                      href="#pricing"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Pricing
                    </a>
                  </li>
                  <li class="relative group">
                    <a
                      href="#team"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Team
                    </a>
                  </li>
                  <li class="relative group">
                    <a
                      href="#contact"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Contact
                    </a>
                  </li>
                  <li class="relative group">
                    <a
                      href="blog-grids.html"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Blog
                    </a>
                  </li>
                  <li class="relative submenu-item group">
                    <a
                      href="javascript:void(0)"
                      class="relative flex items-center justify-between py-2 mx-8 text-base font-medium text-dark group-hover:text-primary dark:text-white lg:ml-8 lg:mr-0 lg:inline-flex lg:py-6 lg:pl-0 lg:pr-4 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Pages

                      <svg
                        class="ml-2 fill-current"
                        width="16"
                        height="20"
                        viewBox="0 0 16 20"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          d="M7.99999 14.9C7.84999 14.9 7.72499 14.85 7.59999 14.75L1.84999 9.10005C1.62499 8.87505 1.62499 8.52505 1.84999 8.30005C2.07499 8.07505 2.42499 8.07505 2.64999 8.30005L7.99999 13.525L13.35 8.25005C13.575 8.02505 13.925 8.02505 14.15 8.25005C14.375 8.47505 14.375 8.82505 14.15 9.05005L8.39999 14.7C8.27499 14.825 8.14999 14.9 7.99999 14.9Z"
                        />
                      </svg>
                    </a>
                    <div
                      class="submenu relative left-0 top-full hidden w-[250px] rounded-xs bg-white p-4 transition-[top] duration-300 group-hover:opacity-100 dark:bg-dark-2 lg:invisible lg:absolute lg:top-[110%] lg:block lg:opacity-0 lg:shadow-lg lg:group-hover:visible lg:group-hover:top-full"
                    >
                      <a
                        href="about.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        About Page
                      </a>
                      <a
                        href="pricing.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Pricing Page
                      </a>
                      <a
                        href="contact.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Contact Page
                      </a>
                      <a
                        href="blog-grids.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Blog Grid Page
                      </a>
                      <a
                        href="blog-details.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Blog Details Page
                      </a>
                      <a
                        href="signup.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Sign Up Page
                      </a>
                      <a
                        href="signin.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        Sign In Page
                      </a>
                      <a
                        href="404.html"
                        class="block rounded-sm px-4 py-[10px] text-sm text-body-color hover:text-primary dark:text-dark-6 dark:hover:text-primary"
                      >
                        404 Page
                      </a>
                    </div>
                  </li>
                </ul>
              </nav>
            </div>
            <div class="flex items-center justify-end pr-16 lg:pr-0">
              <label
                for="themeSwitcher"
                class="inline-flex items-center cursor-pointer"
                aria-label="themeSwitcher"
                name="themeSwitcher"
              >
                <input
                  type="checkbox"
                  name="themeSwitcher"
                  id="themeSwitcher"
                  class="sr-only"
                  v-model="isDark"
                />
                <span class="block text-white dark:hidden">
                  <svg
                    class="fill-current"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M13.3125 1.50001C12.675 1.31251 12.0375 1.16251 11.3625 1.05001C10.875 0.975006 10.35 1.23751 10.1625 1.68751C9.93751 2.13751 10.05 2.70001 10.425 3.00001C13.0875 5.47501 14.0625 9.11251 12.975 12.525C11.775 16.3125 8.25001 18.975 4.16251 19.0875C3.63751 19.0875 3.22501 19.425 3.07501 19.9125C2.92501 20.4 3.15001 20.925 3.56251 21.1875C4.50001 21.75 5.43751 22.2 6.37501 22.5C7.46251 22.8375 8.58751 22.9875 9.71251 22.9875C11.625 22.9875 13.5 22.5 15.1875 21.5625C17.85 20.1 19.725 17.7375 20.55 14.8875C22.1625 9.26251 18.975 3.37501 13.3125 1.50001ZM18.9375 14.4C18.2625 16.8375 16.6125 18.825 14.4 20.0625C12.075 21.3375 9.41251 21.6 6.90001 20.85C6.63751 20.775 6.33751 20.6625 6.07501 20.55C10.05 19.7625 13.35 16.9125 14.5875 13.0125C15.675 9.56251 15 5.92501 12.7875 3.07501C17.5875 4.68751 20.2875 9.67501 18.9375 14.4Z"
                    />
                  </svg>
                </span>
                <span class="hidden text-white dark:block">
                  <svg
                    class="fill-current"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <g clip-path="url(#clip0_2172_3070)">
                      <path
                        d="M12 6.89999C9.18752 6.89999 6.90002 9.18749 6.90002 12C6.90002 14.8125 9.18752 17.1 12 17.1C14.8125 17.1 17.1 14.8125 17.1 12C17.1 9.18749 14.8125 6.89999 12 6.89999ZM12 15.4125C10.125 15.4125 8.58752 13.875 8.58752 12C8.58752 10.125 10.125 8.58749 12 8.58749C13.875 8.58749 15.4125 10.125 15.4125 12C15.4125 13.875 13.875 15.4125 12 15.4125Z"
                      />
                      <path
                        d="M12 4.2375C12.45 4.2375 12.8625 3.8625 12.8625 3.375V1.5C12.8625 1.05 12.4875 0.637497 12 0.637497C11.55 0.637497 11.1375 1.0125 11.1375 1.5V3.4125C11.175 3.8625 11.55 4.2375 12 4.2375Z"
                      />
                      <path
                        d="M12 19.7625C11.55 19.7625 11.1375 20.1375 11.1375 20.625V22.5C11.1375 22.95 11.5125 23.3625 12 23.3625C12.45 23.3625 12.8625 22.9875 12.8625 22.5V20.5875C12.8625 20.1375 12.45 19.7625 12 19.7625Z"
                      />
                      <path
                        d="M18.1125 6.74999C18.3375 6.74999 18.5625 6.67499 18.7125 6.48749L19.9125 5.28749C20.25 4.94999 20.25 4.42499 19.9125 4.08749C19.575 3.74999 19.05 3.74999 18.7125 4.08749L17.5125 5.28749C17.175 5.62499 17.175 6.14999 17.5125 6.48749C17.6625 6.67499 17.8875 6.74999 18.1125 6.74999Z"
                      />
                      <path
                        d="M5.32501 17.5125L4.12501 18.675C3.78751 19.0125 3.78751 19.5375 4.12501 19.875C4.27501 20.025 4.50001 20.1375 4.72501 20.1375C4.95001 20.1375 5.17501 20.0625 5.32501 19.875L6.52501 18.675C6.86251 18.3375 6.86251 17.8125 6.52501 17.475C6.18751 17.175 5.62501 17.175 5.32501 17.5125Z"
                      />
                      <path
                        d="M22.5 11.175H20.5875C20.1375 11.175 19.725 11.55 19.725 12.0375C19.725 12.4875 20.1 12.9 20.5875 12.9H22.5C22.95 12.9 23.3625 12.525 23.3625 12.0375C23.3625 11.55 22.95 11.175 22.5 11.175Z"
                      />
                      <path
                        d="M4.23751 12C4.23751 11.55 3.86251 11.1375 3.37501 11.1375H1.50001C1.05001 11.1375 0.637512 11.5125 0.637512 12C0.637512 12.45 1.01251 12.8625 1.50001 12.8625H3.41251C3.86251 12.8625 4.23751 12.45 4.23751 12Z"
                      />
                      <path
                        d="M18.675 17.5125C18.3375 17.175 17.8125 17.175 17.475 17.5125C17.1375 17.85 17.1375 18.375 17.475 18.7125L18.675 19.9125C18.825 20.0625 19.05 20.175 19.275 20.175C19.5 20.175 19.725 20.1 19.875 19.9125C20.2125 19.575 20.2125 19.05 19.875 18.7125L18.675 17.5125Z"
                      />
                      <path
                        d="M5.32501 4.125C4.98751 3.7875 4.46251 3.7875 4.12501 4.125C3.78751 4.4625 3.78751 4.9875 4.12501 5.325L5.32501 6.525C5.47501 6.675 5.70001 6.7875 5.92501 6.7875C6.15001 6.7875 6.37501 6.7125 6.52501 6.525C6.86251 6.1875 6.86251 5.6625 6.52501 5.325L5.32501 4.125Z"
                      />
                    </g>
                    <defs>
                      <clipPath id="clip0_2172_3070">
                        <rect width="24" height="24" fill="white" />
                      </clipPath>
                    </defs>
                  </svg>
                </span>
              </label>
              <div class="hidden sm:flex">
                <template v-if="isLoggedIn">
                  <Link
                    :href="route('dashboard')"
                    class="loginBtn px-[22px] py-2 text-base font-medium text-white hover:opacity-70"
                  >
                    Dashboard
                  </Link>
                  <Link
                    v-if="canAdmin"
                    :href="route('admin.dashboard')"
                    class="px-6 py-2 text-base font-medium text-white duration-300 ease-in-out rounded-md bg-white/20 signUpBtn hover:bg-white/100 hover:text-dark"
                  >
                    Admin
                  </Link>
                  <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="ms-2 px-6 py-2 text-base font-medium text-white duration-300 ease-in-out rounded-md bg-white/20 hover:bg-white/100 hover:text-dark"
                  >
                    Log out
                  </Link>
                </template>
                <template v-else>
                  <Link
                    :href="route('login')"
                    class="loginBtn px-[22px] py-2 text-base font-medium text-white hover:opacity-70"
                  >
                    Sign In
                  </Link>
                  <Link
                    :href="route('register')"
                    class="px-6 py-2 text-base font-medium text-white duration-300 ease-in-out rounded-md bg-white/20 signUpBtn hover:bg-white/100 hover:text-dark"
                  >
                    Sign Up
                  </Link>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====== Hero Section Start ====== -->
    <div
      id="home"
      class="relative overflow-hidden bg-primary pt-[120px] md:pt-[130px] lg:pt-[160px]"
    >
      <div class="container px-4 mx-auto">
        <div class="flex flex-wrap items-center -mx-4">
          <div class="w-full px-4">
            <div class="mx-auto max-w-[780px] text-center">
              <h1
                class="mb-6 text-3xl font-bold leading-snug text-white sm:text-4xl sm:leading-snug lg:text-5xl lg:leading-[1.2]"
              >
                Eduvidra platform for students and admins
              </h1>

              <p
                class="mx-auto mb-9 max-w-[600px] text-base font-medium text-white sm:text-lg sm:leading-[1.44]"
              >
                Sign in to access your cabinet. New here? Create an account in a
                minute.
              </p>

              <ul class="mb-10 flex flex-wrap items-center justify-center gap-5">
                <template v-if="isLoggedIn">
                  <li>
                    <Link
                      :href="route('dashboard')"
                      class="inline-flex items-center justify-center rounded-md bg-white px-7 py-[14px] text-center text-base font-medium text-dark shadow-1 transition duration-300 ease-in-out hover:bg-gray-2 hover:text-body-color"
                    >
                      Go to dashboard
                    </Link>
                  </li>
                  <li v-if="canAdmin">
                    <Link
                      :href="route('admin.dashboard')"
                      class="flex items-center gap-4 rounded-md bg-white/[0.12] px-6 py-[14px] text-base font-medium text-white transition duration-300 ease-in-out hover:bg-white hover:text-dark"
                    >
                      Admin cabinet
                    </Link>
                  </li>
                </template>
                <template v-else>
                  <li>
                    <Link
                      :href="route('login')"
                      class="inline-flex items-center justify-center rounded-md bg-white px-7 py-[14px] text-center text-base font-medium text-dark shadow-1 transition duration-300 ease-in-out hover:bg-gray-2 hover:text-body-color"
                    >
                      Sign in
                    </Link>
                  </li>
                  <li>
                    <Link
                      :href="route('register')"
                      class="flex items-center gap-4 rounded-md bg-white/[0.12] px-6 py-[14px] text-base font-medium text-white transition duration-300 ease-in-out hover:bg-white hover:text-dark"
                    >
                      Create account
                    </Link>
                  </li>
                </template>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ====== Hero Section End ====== -->

    <!-- ====== Features Section Start ====== -->
    <section class="pb-8 pt-20 dark:bg-dark lg:pb-[70px] lg:pt-[120px]">
      <div class="container px-4 mx-auto">
        <div class="flex flex-wrap -mx-4">
          <div class="w-full px-4">
            <div class="mx-auto mb-12 max-w-[485px] text-center lg:mb-[70px]">
              <span class="mb-2 block text-lg font-semibold text-primary">
                Features
              </span>
              <h2
                class="mb-3 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]"
              >
                What you can do in Eduvidra
              </h2>
              <p class="text-base text-body-color dark:text-dark-6">
                A unified experience for students and admins, built with clear
                navigation and consistent UI.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap -mx-4">
          <div class="w-full px-4 md:w-1/2 lg:w-1/4">
            <div class="group mb-12">
              <div
                class="relative z-10 mb-10 flex h-[70px] w-[70px] items-center justify-center rounded-[14px] bg-primary"
              >
                <span
                  class="absolute left-0 top-0 -z-1 mb-8 flex h-[70px] w-[70px] rotate-[25deg] items-center justify-center rounded-[14px] bg-primary/20 duration-300 group-hover:rotate-45"
                />
                <svg
                  width="36"
                  height="36"
                  viewBox="0 0 36 36"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M30.6 1.012H5.4C2.981 1.012.956 2.981.956 5.456v25.2c0 2.419 1.969 4.388 4.444 4.388h25.2c2.419 0 4.388-1.969 4.388-4.388V5.4c0-2.419-1.969-4.388-4.388-4.388ZM5.4 3.487h25.2c1.012 0 1.856.844 1.856 1.913v5.794H3.487V5.4c0-1.012.9-1.913 1.913-1.913Z"
                    fill="white"
                  />
                </svg>
              </div>
              <h4 class="mb-3 text-xl font-bold text-dark dark:text-white">
                Clean navigation
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Students and admins share one design system and layout patterns.
              </p>
              <a
                href="#"
                class="text-base font-medium text-dark hover:text-primary dark:text-white dark:hover:text-primary"
              >
                Learn more
              </a>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/4">
            <div class="group mb-12">
              <div
                class="relative z-10 mb-10 flex h-[70px] w-[70px] items-center justify-center rounded-[14px] bg-primary"
              >
                <span
                  class="absolute left-0 top-0 -z-1 mb-8 flex h-[70px] w-[70px] rotate-[25deg] items-center justify-center rounded-[14px] bg-primary/20 duration-300 group-hover:rotate-45"
                />
                <svg
                  width="37"
                  height="37"
                  viewBox="0 0 37 37"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M12.355 2.061H5.211C3.299 2.061 1.724 3.636 1.724 5.549v7.144c0 1.913 1.575 3.488 3.487 3.488h7.144c1.913 0 3.488-1.575 3.488-3.488V5.605c.056-1.969-1.519-3.544-3.488-3.544Z"
                    fill="white"
                  />
                  <path
                    d="M31.086 20.005h-7.144c-1.913 0-3.488 1.575-3.488 3.488v7.144c0 1.913 1.575 3.488 3.488 3.488h7.144c1.913 0 3.488-1.575 3.488-3.488v-7.088c0-1.969-1.575-3.544-3.488-3.544Z"
                    fill="white"
                  />
                </svg>
              </div>
              <h4 class="mb-3 text-xl font-bold text-dark dark:text-white">
                Role-ready UI
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Start with one sidebar style; later hide/show items by
                permission.
              </p>
              <a
                href="#"
                class="text-base font-medium text-dark hover:text-primary dark:text-white dark:hover:text-primary"
              >
                Learn more
              </a>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/4">
            <div class="group mb-12">
              <div
                class="relative z-10 mb-10 flex h-[70px] w-[70px] items-center justify-center rounded-[14px] bg-primary"
              >
                <span
                  class="absolute left-0 top-0 -z-1 mb-8 flex h-[70px] w-[70px] rotate-[25deg] items-center justify-center rounded-[14px] bg-primary/20 duration-300 group-hover:rotate-45"
                />
                <svg
                  width="37"
                  height="37"
                  viewBox="0 0 37 37"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M33.561 21.468 31.368 20.118c-.563-.394-1.35-.169-1.744.394-.394.619-.169 1.35.394 1.744l1.462.956-13.331 8.381-13.387-8.381 1.462-.956c.619-.394.788-1.181.394-1.744-.394-.619-1.181-.788-1.744-.394l-2.194 1.35c-.562.394-.956 1.012-.956 1.687 0 .675.338 1.35.956 1.688l14.4 9.056c.338.225.675.281 1.069.281.394 0 .731-.112 1.069-.281l14.344-9c.562-.394.956-1.012.956-1.687 0-.675-.338-1.35-.956-1.744Z"
                    fill="white"
                  />
                </svg>
              </div>
              <h4 class="mb-3 text-xl font-bold text-dark dark:text-white">
                Consistent styling
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Landing, auth, student, and admin pages can share the same color
                tokens.
              </p>
              <a
                href="#"
                class="text-base font-medium text-dark hover:text-primary dark:text-white dark:hover:text-primary"
              >
                Learn more
              </a>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/4">
            <div class="group mb-12">
              <div
                class="relative z-10 mb-10 flex h-[70px] w-[70px] items-center justify-center rounded-[14px] bg-primary"
              >
                <span
                  class="absolute left-0 top-0 -z-1 mb-8 flex h-[70px] w-[70px] rotate-[25deg] items-center justify-center rounded-[14px] bg-primary/20 duration-300 group-hover:rotate-45"
                />
                <svg
                  width="37"
                  height="37"
                  viewBox="0 0 37 37"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M30.58 8.305H5.718c-1.632 0-2.982 1.35-2.982 2.981v3.713c0 1.575 1.294 2.869 2.869 2.925v13.725c0 1.913 1.575 3.544 3.544 3.544h18.394c1.913 0 3.544-1.575 3.544-3.544V17.868c1.406-.225 2.475-1.463 2.475-2.925V11.23c0-1.631-1.35-2.925-2.981-2.925Z"
                    fill="white"
                  />
                </svg>
              </div>
              <h4 class="mb-3 text-xl font-bold text-dark dark:text-white">
                Secure access
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Your RBAC already gates admin routes; the UI can follow the same
                permissions.
              </p>
              <a
                href="#"
                class="text-base font-medium text-dark hover:text-primary dark:text-white dark:hover:text-primary"
              >
                Learn more
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Features Section End ====== -->

    <!-- ====== About Section Start ====== -->
    <section
      id="about"
      class="bg-gray-1 pb-8 pt-20 dark:bg-dark-2 lg:pb-[70px] lg:pt-[120px]"
    >
      <div class="container px-4 mx-auto">
        <div class="flex flex-wrap items-center -mx-4">
          <div class="w-full px-4 lg:w-1/2">
            <div class="mb-12 max-w-[540px] lg:mb-0">
              <h2
                class="mb-5 text-3xl font-bold leading-tight text-dark dark:text-white sm:text-[40px] sm:leading-[1.2]"
              >
                A modern platform experience.
              </h2>
              <p class="mb-10 text-base leading-relaxed text-body-color dark:text-dark-6">
                This page is built from the Play Tailwind template, adapted to
                Inertia + Vue so you can reuse the same styling for the student
                and admin cabinets.
              </p>

              <Link
                :href="route('register')"
                class="inline-flex items-center justify-center rounded-md border border-primary bg-primary px-7 py-3 text-base font-medium text-center text-white hover:border-blue-dark hover:bg-blue-dark"
              >
                Get started
              </Link>
            </div>
          </div>

          <div class="w-full px-4 lg:w-1/2">
            <div class="grid grid-cols-2 gap-4">
              <div class="h-56 rounded-xl bg-white/60 shadow-1 dark:bg-dark" />
              <div class="h-56 rounded-xl bg-white/60 shadow-1 dark:bg-dark" />
              <div
                class="col-span-2 rounded-xl bg-primary px-6 py-10 text-center text-white shadow-2"
              >
                <div class="text-5xl font-extrabold">09</div>
                <div class="mt-1 text-base font-semibold">Years</div>
                <div class="text-base font-medium text-white/70">
                  (Replace this with real metrics)
                </div>
              </div>
            </div>
            <p class="mt-4 text-sm text-body-color dark:text-dark-6">
              Note: images were removed because template assets aren’t in
              `public/` yet.
            </p>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== About Section End ====== -->

    <!-- ====== CTA Section Start ====== -->
    <section class="relative z-10 overflow-hidden bg-primary py-20 lg:py-[115px]">
      <div class="container px-4 mx-auto">
        <div class="mx-auto max-w-[570px] text-center">
          <h2 class="mb-2.5 text-3xl font-bold text-white md:text-[38px] md:leading-[1.44]">
            <span>Ready to explore Eduvidra?</span>
            <span class="text-3xl font-normal md:text-[40px]">Get started now</span>
          </h2>
          <p class="mx-auto mb-6 max-w-[515px] text-base leading-[1.5] text-white">
            Sign in if you already have an account, or register to create one.
          </p>
          <div class="flex flex-wrap items-center justify-center gap-4">
            <Link
              :href="route('login')"
              class="inline-block rounded-md border border-transparent bg-white px-7 py-3 text-base font-medium text-dark transition hover:bg-gray-2"
            >
              Sign in
            </Link>
            <Link
              :href="route('register')"
              class="inline-block rounded-md border border-transparent bg-secondary px-7 py-3 text-base font-medium text-white transition hover:bg-[#0BB489]"
            >
              Create account
            </Link>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== CTA Section End ====== -->

    <!-- ====== Pricing Section Start ====== -->
    <section
      id="pricing"
      class="relative z-20 overflow-hidden bg-white pb-12 pt-20 dark:bg-dark lg:pb-[90px] lg:pt-[120px]"
    >
      <div class="container px-4 mx-auto">
        <div class="flex flex-wrap -mx-4">
          <div class="w-full px-4">
            <div class="mx-auto mb-[60px] max-w-[510px] text-center">
              <span class="mb-2 block text-lg font-semibold text-primary">
                Pricing
              </span>
              <h2
                class="mb-3 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]"
              >
                Simple plans
              </h2>
              <p class="text-base text-body-color dark:text-dark-6">
                Replace these with your real subscription tiers (or remove this
                section if you don’t need pricing).
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap justify-center -mx-4">
          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 overflow-hidden rounded-xl bg-white px-8 py-10 shadow-pricing dark:bg-dark-2 sm:p-12 lg:px-6 lg:py-10 xl:p-14"
            >
              <span class="mb-5 block text-xl font-medium text-dark dark:text-white">
                Starter
              </span>
              <h2 class="mb-11 text-4xl font-semibold text-dark dark:text-white xl:text-[42px] xl:leading-[1.21]">
                <span class="text-xl font-medium">$</span>
                <span class="-ml-1 -tracking-[2px]">0</span>
                <span class="text-base font-normal text-body-color dark:text-dark-6">
                  / month
                </span>
              </h2>
              <div class="mb-[50px]">
                <h5 class="mb-5 text-lg font-medium text-dark dark:text-white">
                  Includes
                </h5>
                <div class="flex flex-col gap-[14px]">
                  <p class="text-base text-body-color dark:text-dark-6">Student cabinet</p>
                  <p class="text-base text-body-color dark:text-dark-6">Basic features</p>
                </div>
              </div>
              <Link
                :href="route('register')"
                class="inline-block rounded-md bg-primary px-7 py-3 text-base font-medium text-center text-white transition hover:bg-blue-dark"
              >
                Start now
              </Link>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 overflow-hidden rounded-xl bg-white px-8 py-10 shadow-pricing dark:bg-dark-2 sm:p-12 lg:px-6 lg:py-10 xl:p-14"
            >
              <p
                class="absolute right-[-50px] top-[60px] inline-block -rotate-90 rounded-bl-md rounded-tl-md bg-primary px-5 py-2 text-base font-medium text-white"
              >
                Popular
              </p>
              <span class="mb-5 block text-xl font-medium text-dark dark:text-white">
                Pro
              </span>
              <h2 class="mb-11 text-4xl font-semibold text-dark dark:text-white xl:text-[42px] xl:leading-[1.21]">
                <span class="text-xl font-medium">$</span>
                <span class="-ml-1 -tracking-[2px]">29</span>
                <span class="text-base font-normal text-body-color dark:text-dark-6">
                  / month
                </span>
              </h2>
              <div class="mb-[50px]">
                <h5 class="mb-5 text-lg font-medium text-dark dark:text-white">
                  Includes
                </h5>
                <div class="flex flex-col gap-[14px]">
                  <p class="text-base text-body-color dark:text-dark-6">Everything in Starter</p>
                  <p class="text-base text-body-color dark:text-dark-6">Admin tools</p>
                </div>
              </div>
              <a
                href="#contact"
                class="inline-block rounded-md bg-primary px-7 py-3 text-base font-medium text-center text-white transition hover:bg-blue-dark"
              >
                Contact us
              </a>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 overflow-hidden rounded-xl bg-white px-8 py-10 shadow-pricing dark:bg-dark-2 sm:p-12 lg:px-6 lg:py-10 xl:p-14"
            >
              <span class="mb-5 block text-xl font-medium text-dark dark:text-white">
                Enterprise
              </span>
              <h2 class="mb-11 text-4xl font-semibold text-dark dark:text-white xl:text-[42px] xl:leading-[1.21]">
                Custom
              </h2>
              <div class="mb-[50px]">
                <h5 class="mb-5 text-lg font-medium text-dark dark:text-white">
                  Includes
                </h5>
                <div class="flex flex-col gap-[14px]">
                  <p class="text-base text-body-color dark:text-dark-6">Custom onboarding</p>
                  <p class="text-base text-body-color dark:text-dark-6">SLA & support</p>
                </div>
              </div>
              <a
                href="#contact"
                class="inline-block rounded-md bg-primary px-7 py-3 text-base font-medium text-center text-white transition hover:bg-blue-dark"
              >
                Talk to us
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Pricing Section End ====== -->

    <!-- ====== FAQ Section Start ====== -->
    <section class="relative z-20 overflow-hidden bg-gray-1 py-20 dark:bg-dark-2 lg:py-[120px]">
      <div class="container px-4 mx-auto">
        <div class="mx-auto mb-[60px] max-w-[520px] text-center">
          <span class="mb-2 block text-lg font-semibold text-primary">FAQ</span>
          <h2 class="mb-3 text-3xl font-bold leading-[1.2] text-dark dark:text-white sm:text-4xl md:text-[40px]">
            Questions
          </h2>
          <p class="text-base text-body-color dark:text-dark-6">
            Replace with your real onboarding and platform questions.
          </p>
        </div>

        <div class="mx-auto max-w-[800px] space-y-6">
          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              How do I access the admin cabinet?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              Admin routes are protected by permissions; only users with admin
              access can enter.
            </p>
          </div>

          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              Can students see admin pages?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              No—RBAC blocks it server-side, and the UI can hide admin menu
              items.
            </p>
          </div>

          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              Can we have different sidebars later?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              Yes. Start with a shared sidebar component, then split into
              `StudentSidebar` and `AdminSidebar` later.
            </p>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== FAQ Section End ====== -->

    <!-- ====== Team Section Start ====== -->
    <section
      id="team"
      class="bg-white py-20 dark:bg-dark lg:py-[120px]"
    >
      <div class="container px-4 mx-auto">
        <div class="mx-auto mb-[60px] max-w-[520px] text-center">
          <span class="mb-2 block text-lg font-semibold text-primary">
            Team
          </span>
          <h2 class="mb-3 text-3xl font-bold leading-[1.2] text-dark dark:text-white sm:text-4xl md:text-[40px]">
            Our team
          </h2>
          <p class="text-base text-body-color dark:text-dark-6">
            Add real people and photos later.
          </p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-xl bg-gray-1 p-6 shadow-1 dark:bg-dark-2">
            <div class="h-28 w-28 rounded-full bg-white/60 shadow-1 dark:bg-dark" />
            <div class="mt-4 text-lg font-semibold text-dark dark:text-white">
              Person One
            </div>
            <div class="text-sm text-body-color dark:text-dark-6">
              Role
            </div>
          </div>
          <div class="rounded-xl bg-gray-1 p-6 shadow-1 dark:bg-dark-2">
            <div class="h-28 w-28 rounded-full bg-white/60 shadow-1 dark:bg-dark" />
            <div class="mt-4 text-lg font-semibold text-dark dark:text-white">
              Person Two
            </div>
            <div class="text-sm text-body-color dark:text-dark-6">
              Role
            </div>
          </div>
          <div class="rounded-xl bg-gray-1 p-6 shadow-1 dark:bg-dark-2">
            <div class="h-28 w-28 rounded-full bg-white/60 shadow-1 dark:bg-dark" />
            <div class="mt-4 text-lg font-semibold text-dark dark:text-white">
              Person Three
            </div>
            <div class="text-sm text-body-color dark:text-dark-6">
              Role
            </div>
          </div>
          <div class="rounded-xl bg-gray-1 p-6 shadow-1 dark:bg-dark-2">
            <div class="h-28 w-28 rounded-full bg-white/60 shadow-1 dark:bg-dark" />
            <div class="mt-4 text-lg font-semibold text-dark dark:text-white">
              Person Four
            </div>
            <div class="text-sm text-body-color dark:text-dark-6">
              Role
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Team Section End ====== -->

    <!-- ====== Blog Section Start ====== -->
    <section class="bg-gray-1 py-20 dark:bg-dark-2 lg:py-[120px]">
      <div class="container px-4 mx-auto">
        <div class="mx-auto mb-[60px] max-w-[520px] text-center">
          <span class="mb-2 block text-lg font-semibold text-primary">Blog</span>
          <h2 class="mb-3 text-3xl font-bold leading-[1.2] text-dark dark:text-white sm:text-4xl md:text-[40px]">
            Latest updates
          </h2>
          <p class="text-base text-body-color dark:text-dark-6">
            Optional section—keep or remove depending on your roadmap.
          </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <div class="text-sm text-body-color dark:text-dark-6">Apr 2026</div>
            <div class="mt-2 text-lg font-semibold text-dark dark:text-white">
              First release
            </div>
            <p class="mt-2 text-body-color dark:text-dark-6">
              A short post teaser goes here.
            </p>
          </div>
          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <div class="text-sm text-body-color dark:text-dark-6">Apr 2026</div>
            <div class="mt-2 text-lg font-semibold text-dark dark:text-white">
              Improvements
            </div>
            <p class="mt-2 text-body-color dark:text-dark-6">
              A short post teaser goes here.
            </p>
          </div>
          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <div class="text-sm text-body-color dark:text-dark-6">Apr 2026</div>
            <div class="mt-2 text-lg font-semibold text-dark dark:text-white">
              Roadmap
            </div>
            <p class="mt-2 text-body-color dark:text-dark-6">
              A short post teaser goes here.
            </p>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Blog Section End ====== -->

    <!-- ====== Contact Start ====== -->
    <section id="contact" class="relative py-20 md:py-[120px]">
      <div class="container px-4 mx-auto">
        <div class="-mx-4 flex flex-wrap">
          <div class="w-full px-4 lg:w-5/12">
            <div class="mb-12 max-w-[470px] lg:mb-0">
              <span class="mb-2 block text-lg font-semibold text-primary">
                Contact us
              </span>
              <h2 class="mb-3 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]">
                Let’s talk
              </h2>
              <p class="mb-8 text-base text-body-color dark:text-dark-6">
                Hook this form to a Laravel route later. For now it’s a static
                UI section.
              </p>
            </div>
          </div>

          <div class="w-full px-4 lg:w-7/12">
            <div class="rounded-xl bg-white p-8 shadow-2 dark:bg-dark">
              <form>
                <div class="-mx-4 flex flex-wrap">
                  <div class="w-full px-4 md:w-1/2">
                    <label class="mb-2 block text-sm font-medium text-dark dark:text-white">
                      Full name
                    </label>
                    <input
                      type="text"
                      class="mb-6 w-full rounded-md border border-stroke px-4 py-3 text-body-color shadow-input focus:border-primary focus:outline-none dark:border-dark-3 dark:bg-dark-2 dark:text-dark-6"
                      placeholder="John Doe"
                    />
                  </div>
                  <div class="w-full px-4 md:w-1/2">
                    <label class="mb-2 block text-sm font-medium text-dark dark:text-white">
                      Email
                    </label>
                    <input
                      type="email"
                      class="mb-6 w-full rounded-md border border-stroke px-4 py-3 text-body-color shadow-input focus:border-primary focus:outline-none dark:border-dark-3 dark:bg-dark-2 dark:text-dark-6"
                      placeholder="you@example.com"
                    />
                  </div>
                  <div class="w-full px-4">
                    <label class="mb-2 block text-sm font-medium text-dark dark:text-white">
                      Message
                    </label>
                    <textarea
                      rows="5"
                      class="mb-6 w-full rounded-md border border-stroke px-4 py-3 text-body-color shadow-input focus:border-primary focus:outline-none dark:border-dark-3 dark:bg-dark-2 dark:text-dark-6"
                      placeholder="Tell us what you need…"
                    />
                  </div>
                  <div class="w-full px-4">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center rounded-md bg-primary px-7 py-3 text-base font-medium text-white transition hover:bg-blue-dark"
                    >
                      Send message
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Contact End ====== -->

    <!-- ====== Footer Section Start ====== -->
    <footer class="relative z-10 bg-dark pt-20 lg:pt-[120px]">
      <div class="container px-4 mx-auto">
        <div class="-mx-4 flex flex-wrap">
          <div class="w-full px-4 sm:w-2/3 lg:w-3/12">
            <div class="mb-10 w-full">
              <Link href="/" class="mb-6 inline-flex items-center">
                <span class="text-xl font-bold tracking-tight text-white">
                  {{ brandName }}
                </span>
              </Link>
              <p class="mb-7 text-base text-white/70">
                Eduvidra — built with Laravel, Inertia, Vue, and Tailwind.
              </p>
            </div>
          </div>

          <div class="w-full px-4 sm:w-1/2 lg:w-3/12">
            <div class="mb-10 w-full">
              <h4 class="mb-9 text-lg font-semibold text-white">Links</h4>
              <ul class="space-y-3">
                <li>
                  <a href="#about" class="text-base text-white/70 hover:text-white">
                    About
                  </a>
                </li>
                <li>
                  <a href="#pricing" class="text-base text-white/70 hover:text-white">
                    Pricing
                  </a>
                </li>
                <li>
                  <a href="#contact" class="text-base text-white/70 hover:text-white">
                    Contact
                  </a>
                </li>
              </ul>
            </div>
          </div>

          <div class="w-full px-4 sm:w-1/2 lg:w-3/12">
            <div class="mb-10 w-full">
              <h4 class="mb-9 text-lg font-semibold text-white">Account</h4>
              <ul class="space-y-3">
                <li>
                  <Link :href="route('login')" class="text-base text-white/70 hover:text-white">
                    Sign in
                  </Link>
                </li>
                <li>
                  <Link :href="route('register')" class="text-base text-white/70 hover:text-white">
                    Register
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="border-t border-white/10 py-8">
          <p class="text-center text-sm text-white/60">
            © {{ year }} {{ brandName }}. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
    <!-- ====== Footer Section End ====== -->
</template>
