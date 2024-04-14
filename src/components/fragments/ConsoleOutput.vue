<template>
    <div class="console">
        <section class="console__header">
            <div>
                <h1 class="console__headline">{{ title }}</h1>
                <p class="console__description" v-if="description">{{ description }}</p>
            </div>
            <div class="console__actions">
                <button
                    class="widget-button widget-button--transparent widget-button--console console__action"
                    :class="{ 'console__action--active': showConsole }"
                    :title="$t('ui.console.toggle')"
                    @click="$emit('update:showConsole', !showConsole)"
                    v-if="!forceConsole"
                />
                <button-menu transparent button-class="console__action">
                    <button @click="showLog">{{ $t('ui.console.showLog') }}</button>
                    <button v-clipboard="consoleOutput">{{ $t('ui.console.copyLog') }}</button>
                </button-menu>
            </div>
        </section>
        <div class="console__operations">
            <template v-for="(operation, i) in operations">
                <console-operation
                    v-bind="operation"
                    :show-console="showConsole"
                    :force-console="forceConsole"
                    :key="i"
                />
            </template>
        </div>
    </div>
</template>

<script>
    import ButtonMenu from '../widgets/ButtonMenu';
    import ConsoleOperation from './ConsoleOperation';

    export default {
        components: { ConsoleOperation, ButtonMenu },

        props: {
            title: {
                type: String,
                required: true,
            },
            description: String,
            operations: {
                type: Array,
                required: true
            },
            showConsole: {
                type: Boolean,
                default: false,
            },
            forceConsole: {
                type: Boolean,
                default: false,
            },
            consoleOutput: String,
        },

        methods: {
            showLog() {
                const popup = window.open();

                if (popup) {
                    popup.document.open();
                    popup.document.write(`<pre>${this.consoleOutput}</pre>`);
                    popup.document.close();
                }
            },
        },
    }
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    // ToDo:
    .console {
        background: #24292e;

        &__header {
            display: flex;
            flex-wrap: nowrap;
            justify-content: space-between;
            align-items: center;
            padding: 12px 12px 12px 24px;
            border-bottom: 1px solid #444D56;
        }

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

        &__operations {
            padding: 20px 0;
        }
    }
</style>
