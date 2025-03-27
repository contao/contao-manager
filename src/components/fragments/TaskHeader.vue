<template>
    <section class="task-header">
        <h1 class="task-header__headline">{{ taskTitle }}</h1>
        <!--<p class="task-header__description">{{ $t(`ui.task.${taskStatus}.details`) }}</p>-->
        <div class="task-header__actions">
            <button
                class="widget-button widget-button--transparent widget-button--console task-header__action"
                :class="{ 'task-header__action--active': showConsole }"
                :title="$t('ui.task.toggleConsole')"
                @click="$emit('update:showConsole', !showConsole)"
            />
            <button-menu transparent button-class="task-header__action">
                <button @click="showLog">{{ $t('ui.task.showLog') }}</button>
                <button v-clipboard="currentTask.console">{{ $t('ui.task.copyLog') }}</button>
            </button-menu>
        </div>
    </section>
</template>

<script>
import task from '../../mixins/task';
import ButtonMenu from '../widgets/ButtonMenu';

export default {
    name: 'TaskHeader',
    components: { ButtonMenu },
    mixins: [task],

    props: {
        showConsole: Boolean,
    },

    computed: {
        taskTitle: (vm) => (vm.hasTask ? vm.currentTask.title : vm.$t('ui.task.loading')),
    },

    methods: {
        showLog() {
            const popup = window.open();

            if (popup) {
                popup.document.open();
                popup.document.write(`<pre>${this.currentTask.console}</pre>`);
                popup.document.close();
            }
        },

        copyLog() {},
    },
};
</script>

<style lang="scss">
.task-header {
    display: flex;
    flex-wrap: nowrap;
    justify-content: space-between;
    align-items: center;
    padding: 12px 12px 12px 24px;
    border-bottom: 1px solid #444d56;

    &__headline {
        margin: 0;
        font-size: inherit;
        line-height: 1.5;
        color: #fff;
    }

    &__description {
        color: #959da5;
        font-size: 12px;
    }

    &__actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
    }

    &__action,
    &__action > button {
        height: 30px !important;
        line-height: 30px !important;
        width: auto !important;
        min-width: 0;
        margin: 0 2px;
        padding: 0 5px !important;
        border: none !important;

        &:hover {
            background-color: #2f363d !important;
        }

        &--active {
            background-color: #586069 !important;
        }
    }
}
</style>
