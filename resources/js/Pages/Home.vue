<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import SearchBox from '@/Components/Shared/SearchBox.vue';
import { RbacPermission } from '@/constants/rbac';

const brandName = 'Eduvidra';
const year = new Date().getFullYear();

const page = usePage();
const seo = computed(() => page.props.seo);
const user = computed(() => page.props.auth?.user ?? null);
const isLoggedIn = computed(() => !!user.value);
const canAdmin = computed(() =>
  (user.value?.permissions ?? []).includes(RbacPermission.ADMIN_ACCESS),
);

const primaryDashboardRouteName = computed(() => (canAdmin.value ? 'admin.dashboard' : 'dashboard'));
const primaryDashboardLabel = computed(() => (canAdmin.value ? 'Go to admin dashboard' : 'Go to dashboard'));

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
    <Head :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description" />
        <link head-key="canonical" rel="canonical" :href="seo.canonical" />
        <meta head-key="og:type" property="og:type" :content="seo.type" />
        <meta head-key="og:site_name" property="og:site_name" :content="seo.site_name" />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta head-key="og:url" property="og:url" :content="seo.url" />
        <meta head-key="og:image" property="og:image" :content="seo.image" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="seo.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <meta head-key="twitter:image" name="twitter:image" :content="seo.image" />
    </Head>

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
                      href="#blog"
                      class="flex py-2 mx-8 text-base font-medium ud-menu-scroll text-dark group-hover:text-primary dark:text-white lg:ml-7 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-white lg:group-hover:text-white lg:group-hover:opacity-70 xl:ml-10"
                    >
                      Updates
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
                    :href="route(primaryDashboardRouteName)"
                    class="loginBtn px-[22px] py-2 text-base font-medium text-white hover:opacity-70"
                  >
                    Dashboard
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
                Learn online, at your own pace
              </h1>

              <p
                class="mx-auto mb-9 max-w-[600px] text-base font-medium text-white sm:text-lg sm:leading-[1.44]"
              >
                Eduvidra brings your courses, lessons, and progress together in
                one simple dashboard — so you can focus on learning, not finding
                your way around.
              </p>

              <div class="mb-10 w-full max-w-xl mx-auto">
                <SearchBox size="lg" />
              </div>
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
                Everything you need to keep learning
              </h2>
              <p class="text-base text-body-color dark:text-dark-6">
                From your first lesson to your latest course, Eduvidra keeps the
                experience simple, organized, and yours.
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
                Structured courses
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Work through courses and lessons in a clear order, so you always
                know what comes next.
              </p>
              <a
                href="#about"
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
                Track your progress
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Pick up right where you left off and see how far you've come at a
                glance.
              </p>
              <a
                href="#about"
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
                One personal dashboard
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Your courses, schedule, and materials live together in a single,
                tidy place.
              </p>
              <a
                href="#about"
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
                Secure by default
              </h4>
              <p class="mb-8 text-body-color dark:text-dark-6 lg:mb-9">
                Two-factor authentication keeps your account and your work safe,
                every time you sign in.
              </p>
              <a
                href="#about"
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
              <span class="mb-2 block text-lg font-semibold text-primary">
                About Eduvidra
              </span>
              <h2
                class="mb-5 text-3xl font-bold leading-tight text-dark dark:text-white sm:text-[40px] sm:leading-[1.2]"
              >
                A learning experience built around you.
              </h2>
              <p class="mb-10 text-base leading-relaxed text-body-color dark:text-dark-6">
                Eduvidra is an online learning platform that keeps everything in
                one place. Sign in to your personal dashboard, continue your
                courses, and learn at a pace that works for you — on any device,
                whenever you have time.
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
            <div class="rounded-xl bg-white p-8 shadow-2 dark:bg-dark sm:p-10">
              <h3 class="mb-6 text-xl font-bold text-dark dark:text-white">
                Why students choose Eduvidra
              </h3>
              <ul class="space-y-5">
                <li class="flex items-start gap-4">
                  <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor" />
                    </svg>
                  </span>
                  <div>
                    <p class="font-semibold text-dark dark:text-white">Learn at your own pace</p>
                    <p class="text-sm text-body-color dark:text-dark-6">Start, pause, and resume lessons whenever it suits you.</p>
                  </div>
                </li>
                <li class="flex items-start gap-4">
                  <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor" />
                    </svg>
                  </span>
                  <div>
                    <p class="font-semibold text-dark dark:text-white">Everything in one dashboard</p>
                    <p class="text-sm text-body-color dark:text-dark-6">Courses, materials, and progress — no more digging around.</p>
                  </div>
                </li>
                <li class="flex items-start gap-4">
                  <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor" />
                    </svg>
                  </span>
                  <div>
                    <p class="font-semibold text-dark dark:text-white">Your account stays secure</p>
                    <p class="text-sm text-body-color dark:text-dark-6">Two-factor authentication protects your progress and data.</p>
                  </div>
                </li>
              </ul>
            </div>
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
            <span>Ready to start learning? </span>
            <span class="text-3xl font-normal md:text-[40px]">Create your free account</span>
          </h2>
          <p class="mx-auto mb-6 max-w-[515px] text-base leading-[1.5] text-white">
            <template v-if="isLoggedIn">
              Continue to your dashboard and manage your learning space.
            </template>
            <template v-else>
              Sign in if you already have an account, or register to create one.
            </template>
          </p>
          <div class="flex flex-wrap items-center justify-center gap-4">
            <template v-if="isLoggedIn">
              <Link
                :href="route(primaryDashboardRouteName)"
                class="inline-block rounded-md border border-transparent bg-white px-7 py-3 text-base font-medium text-dark transition hover:bg-gray-2"
              >
                {{ primaryDashboardLabel }}
              </Link>
            </template>
            <template v-else>
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
            </template>
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
                Simple, flexible plans
              </h2>
              <p class="text-base text-body-color dark:text-dark-6">
                Start free and upgrade when you're ready. No hidden fees — just
                the tools you need to keep learning.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap justify-center -mx-4">
          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 flex h-full flex-col overflow-hidden rounded-xl bg-white px-8 py-10 shadow-pricing transition duration-300 hover:-translate-y-1 dark:bg-dark-2 sm:p-12 lg:px-8 lg:py-10 xl:p-12"
            >
              <span class="mb-2 block text-xl font-semibold text-dark dark:text-white">
                Free
              </span>
              <p class="mb-6 text-sm text-body-color dark:text-dark-6">
                For learners getting started.
              </p>
              <h2 class="mb-8 text-4xl font-bold text-dark dark:text-white xl:text-[42px] xl:leading-[1.2]">
                <span class="mr-1.5 text-xl font-medium">$</span>
                <span class="-tracking-[2px]">0</span>
                <span class="text-base font-normal text-body-color dark:text-dark-6">
                  / month
                </span>
              </h2>
              <ul class="mb-9 flex flex-col gap-4">
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Personal learning dashboard
                </li>
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Access to free courses
                </li>
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Progress tracking
                </li>
              </ul>
              <Link
                :href="route('register')"
                class="mt-auto inline-block rounded-md border border-primary bg-transparent px-7 py-3 text-center text-base font-medium text-primary transition hover:bg-primary hover:text-white"
              >
                Get started free
              </Link>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 flex h-full flex-col overflow-hidden rounded-xl bg-primary px-8 py-10 shadow-pricing transition duration-300 hover:-translate-y-1 sm:p-12 lg:px-8 lg:py-10 xl:p-12"
            >
              <span
                class="absolute right-5 top-6 inline-block rounded-full bg-white/20 px-4 py-1 text-sm font-medium text-white"
              >
                Most popular
              </span>
              <span class="mb-2 block text-xl font-semibold text-white">
                Pro
              </span>
              <p class="mb-6 text-sm text-white/70">
                For learners who want it all.
              </p>
              <h2 class="mb-8 text-4xl font-bold text-white xl:text-[42px] xl:leading-[1.2]">
                <span class="mr-1.5 text-xl font-medium">$</span>
                <span class="-tracking-[2px]">29</span>
                <span class="text-base font-normal text-white/70">
                  / month
                </span>
              </h2>
              <ul class="mb-9 flex flex-col gap-4">
                <li class="flex items-center gap-3 text-base text-white">
                  <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Everything in Free
                </li>
                <li class="flex items-center gap-3 text-base text-white">
                  <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Unlimited courses
                </li>
                <li class="flex items-center gap-3 text-base text-white">
                  <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Certificates of completion
                </li>
                <li class="flex items-center gap-3 text-base text-white">
                  <span><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Priority support
                </li>
              </ul>
              <Link
                :href="route('register')"
                class="mt-auto inline-block rounded-md bg-white px-7 py-3 text-center text-base font-medium text-primary transition hover:bg-gray-2"
              >
                Upgrade to Pro
              </Link>
            </div>
          </div>

          <div class="w-full px-4 md:w-1/2 lg:w-1/3">
            <div
              class="relative z-10 mb-10 flex h-full flex-col overflow-hidden rounded-xl bg-white px-8 py-10 shadow-pricing transition duration-300 hover:-translate-y-1 dark:bg-dark-2 sm:p-12 lg:px-8 lg:py-10 xl:p-12"
            >
              <span class="mb-2 block text-xl font-semibold text-dark dark:text-white">
                Institution
              </span>
              <p class="mb-6 text-sm text-body-color dark:text-dark-6">
                For schools and teams.
              </p>
              <h2 class="mb-8 text-4xl font-bold text-dark dark:text-white xl:text-[42px] xl:leading-[1.21]">
                Custom
              </h2>
              <ul class="mb-9 flex flex-col gap-4">
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Everything in Pro
                </li>
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Admin tools & user management
                </li>
                <li class="flex items-center gap-3 text-base text-body-color dark:text-dark-6">
                  <span class="text-primary"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.2 11.1 2.9 7.8l1.1-1.1 2.2 2.2L11.9 3.4 13 4.5l-6.8 6.6Z" fill="currentColor"/></svg></span>
                  Dedicated onboarding & support
                </li>
              </ul>
              <a
                href="#contact"
                class="mt-auto inline-block rounded-md border border-primary bg-transparent px-7 py-3 text-center text-base font-medium text-primary transition hover:bg-primary hover:text-white"
              >
                Contact sales
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
            Frequently asked questions
          </h2>
          <p class="text-base text-body-color dark:text-dark-6">
            Everything you need to know about getting started with Eduvidra.
          </p>
        </div>

        <div class="mx-auto max-w-[800px] space-y-6">
          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              How do I get started?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              Create a free account in under a minute, set up your sign-in, and
              you'll land straight in your personal dashboard ready to learn.
            </p>
          </div>

          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              Does it cost anything to try?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              No. The Free plan lets you explore courses and track your progress
              at no cost. You can upgrade to Pro any time you want more.
            </p>
          </div>

          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              Can I learn on my phone?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              Yes. Eduvidra works in any modern browser, so you can pick up your
              courses on a phone, tablet, or laptop whenever it suits you.
            </p>
          </div>

          <div class="rounded-xl bg-white p-6 shadow-1 dark:bg-dark">
            <h3 class="text-lg font-semibold text-dark dark:text-white">
              Is my account secure?
            </h3>
            <p class="mt-2 text-body-color dark:text-dark-6">
              Absolutely. Every account supports two-factor authentication, so
              your progress and personal details stay protected.
            </p>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== FAQ Section End ====== -->

    <!-- ====== Blog Section Start ====== -->
    <section id="blog" class="bg-gray-1 py-20 dark:bg-dark-2 lg:py-[120px]">
      <div class="container px-4 mx-auto">
        <div class="mx-auto mb-[60px] max-w-[520px] text-center">
          <span class="mb-2 block text-lg font-semibold text-primary">Updates</span>
          <h2 class="mb-3 text-3xl font-bold leading-[1.2] text-dark dark:text-white sm:text-4xl md:text-[40px]">
            News &amp; learning tips
          </h2>
          <p class="text-base text-body-color dark:text-dark-6">
            Product updates and practical advice to help you get the most out of
            Eduvidra.
          </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
          <article class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-1 transition duration-300 hover:shadow-2 dark:bg-dark">
            <div class="flex h-40 items-center justify-center bg-primary/10 text-primary">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div class="flex flex-1 flex-col p-6">
              <span class="mb-3 inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">Product</span>
              <h3 class="mb-2 text-lg font-semibold text-dark dark:text-white">
                A fresh new dashboard
              </h3>
              <p class="mb-4 text-sm text-body-color dark:text-dark-6">
                Your courses and progress now live on one redesigned home screen,
                so picking up where you left off takes a single click.
              </p>
              <a href="#" class="mt-auto text-sm font-medium text-primary hover:underline">Read more</a>
            </div>
          </article>

          <article class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-1 transition duration-300 hover:shadow-2 dark:bg-dark">
            <div class="flex h-40 items-center justify-center bg-primary/10 text-primary">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <div class="flex flex-1 flex-col p-6">
              <span class="mb-3 inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">Learning tips</span>
              <h3 class="mb-2 text-lg font-semibold text-dark dark:text-white">
                Building a study habit that sticks
              </h3>
              <p class="mb-4 text-sm text-body-color dark:text-dark-6">
                Short, consistent sessions beat marathon cramming. Here's how to
                use progress tracking to keep your momentum going.
              </p>
              <a href="#" class="mt-auto text-sm font-medium text-primary hover:underline">Read more</a>
            </div>
          </article>

          <article class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-1 transition duration-300 hover:shadow-2 dark:bg-dark">
            <div class="flex h-40 items-center justify-center bg-primary/10 text-primary">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="flex flex-1 flex-col p-6">
              <span class="mb-3 inline-flex w-fit rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">Security</span>
              <h3 class="mb-2 text-lg font-semibold text-dark dark:text-white">
                Protect your account with 2FA
              </h3>
              <p class="mb-4 text-sm text-body-color dark:text-dark-6">
                Two-factor authentication adds a second layer of protection in
                seconds. Here's how to switch it on for your account.
              </p>
              <a href="#" class="mt-auto text-sm font-medium text-primary hover:underline">Read more</a>
            </div>
          </article>
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
                Questions about courses, plans, or bringing Eduvidra to your
                school? Send us a message and we'll get back to you.
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
                Eduvidra is an online learning platform that brings your courses,
                progress, and materials together in one simple place.
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
                <template v-if="isLoggedIn">
                  <li>
                    <Link :href="route(primaryDashboardRouteName)" class="text-base text-white/70 hover:text-white">
                      {{ primaryDashboardLabel }}
                    </Link>
                  </li>
                </template>
                <template v-else>
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
                </template>
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
