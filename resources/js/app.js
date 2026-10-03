import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { abilitiesPlugin } from '@casl/vue';
import { buildRules } from './ability';
import { createMongoAbility } from '@casl/ability';
import { addIcons, OhVueIcon } from 'oh-vue-icons';
import {
    HiHome,
    HiPencil,
    HiUsers,
    HiAdjustments,
    HiFlag,
    HiFolder,
    HiDocumentText,
    HiCog,
    HiUserGroup,
    HiArrowLeft,
    HiArrowUp,
    HiArrowDown,
    HiTrendingDown,
    HiMinus,
    HiPlusCircle,
    HiTrash,
    HiExclamationCircle,
    HiMenu,
    HiX,
    HiCheckCircle,
    HiSearch,
    HiPlus,
    HiLockClosed,
    HiClock,
    HiTrendingUp,
    HiLogout,
    HiChevronLeft,
    HiChevronRight,
    HiDownload,
    HiPrinter,
    HiDocumentDuplicate,
    HiEye,
    HiEyeOff,
    HiKey,
    HiUser,
    HiInformationCircle,
    HiDeviceMobile,
    HiChartPie,
    HiCalendar,
} from 'oh-vue-icons/icons/hi';
import { BiTrophy, BiFolderPlus, BiPencilSquare, BiLaptop } from 'oh-vue-icons/icons/bi';
import { CoMagnifyingGlass } from 'oh-vue-icons/icons/co';
import { OiSidebarCollapse, OiSidebarExpand } from 'oh-vue-icons/icons/oi';

import AppDatePicker from './Components/AppDatePicker.vue';
import '@vuepic/vue-datepicker/dist/main.css';

import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

addIcons(
    HiHome,
    HiPencil,
    HiUsers,
    HiAdjustments,
    BiTrophy,
    BiFolderPlus,
    HiFlag,
    HiFolder,
    HiDocumentText,
    HiCog,
    HiUserGroup,
    HiArrowLeft,
    HiArrowUp,
    HiArrowDown,
    HiTrendingDown,
    HiMinus,
    HiPlusCircle,
    HiTrash,
    HiExclamationCircle,
    HiMenu,
    HiX,
    HiCheckCircle,
    HiSearch,
    CoMagnifyingGlass,
    HiPlus,
    BiPencilSquare,
    BiLaptop,
    HiLockClosed,
    HiClock,
    HiTrendingUp,
    HiLogout,
    OiSidebarCollapse,
    OiSidebarExpand,
    HiChevronLeft,
    HiChevronRight,
    HiDownload,
    HiPrinter,
    HiDocumentDuplicate,
    HiEye,
    HiEyeOff,
    HiKey,
    HiUser,
    HiInformationCircle,
    HiDeviceMobile,
    HiChartPie,
    HiCalendar,
);

if (document.getElementById('app')) {
    createInertiaApp({
        title: (title) => title ? `${title} - PhysicalScore` : 'PhysicalScore',
        resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            // Build initial ability from auth.user shared by Inertia
            const initialUser = props.initialPage.props.auth?.user || null;
            const ability = createMongoAbility(buildRules(initialUser));

            const vueApp = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(VueSweetalert2)
                .use(abilitiesPlugin, ability)
                .use(ZiggyVue)
                .component('v-icon', OhVueIcon)
                .component('VueDatePicker', AppDatePicker)
                .mount(el);

            // Keep abilities in sync after every Inertia navigation
            router.on('navigate', (event) => {
                const user = event.detail.page.props.auth?.user || null;
                ability.update(buildRules(user));
            });
        },
        progress: {
            color: '#06A77D',
        },
    });
}
