<template>
    <component ref="details" :is="console && !forceConsole ? 'details' : 'div'" class="console-operation" @toggle="toggleConsole">
        <component :is="console && !forceConsole ? 'summary' : 'div'" class="console-operation__summary" :class="{ 'console-operation__summary--console': !!console }">
            <div class="console-operation__status">
                <svg v-if="isActive" class="console-operation__icon console-operation__icon--active" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#dbab0a">
                    <g fill="none" fillrule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle opacity=".5" cx="8" cy="8" r="7"></circle><path d=" M 15 8 A 7 7 0 0 1 8 15"></path></g></g>
                    <path fill-rule="evenodd" d="M9 5a4 4 0 100 8 4 4 0 000-8z"></path>
                </svg>
                <svg v-else-if="isSuccess" class="console-operation__icon console-operation__icon--success" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"></path></svg>
                <svg v-else-if="isError || isStopped" class="console-operation__icon console-operation__icon--error" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M3.72 3.72a.75.75 0 011.06 0L8 6.94l3.22-3.22a.75.75 0 111.06 1.06L9.06 8l3.22 3.22a.75.75 0 11-1.06 1.06L8 9.06l-3.22 3.22a.75.75 0 01-1.06-1.06L6.94 8 3.72 4.78a.75.75 0 010-1.06z"></path></svg>
                <svg v-else-if="isSkipped" class="console-operation__icon console-operation__icon--skipped" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8z"></path></svg>
                <svg v-else class="console-operation__icon console-operation__icon--pending" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8z"></path></svg>
            </div>
            <div class="console-operation__label">
                <template v-if="Array.isArray(summary)">
                    <template v-for="(title, k) in summary">
                        <h2 class="console-operation__title" :class="{ 'console-operation__title--disabled': title.match(/^~.+~$/) }" :key="`${k}_title`">{{ title.replace(/^~(.+)~$/, '$1') }}</h2>
                        <p class="console-operation__description" :key="`${k}_details`" v-if="details[k]">{{ details[k] }}</p>
                        <br :key="`${k}_br`"/>
                    </template>
                </template>
                <template v-else>
                    <h2 class="console-operation__title" :class="{ 'console-operation__title--disabled': summary.match(/^~.+~$/) }">{{ summary.replace(/^~(.+)~$/, '$1') }}</h2>
                    <p class="console-operation__description" v-if="details">{{ details }}</p>
                </template>
            </div>
        </component>

        <div class="console-operation__console" v-if="console">
            <button class="console-operation__scroll console-operation__scroll--top" @click="scrollToTop" v-show="!isScrolledTop">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>
            </button>
            <div ref="console" @scroll="scrolled" class="console-operation__lines">
                <template v-for="(line, i) in consoleLines">
                    <div class="console-operation__line" :data-index="i" :key="i">
                        <span class="console-operation__line-number">{{ i + 1 }}</span>
                        <span class="console-operation__line-content">{{ line }}</span>
                    </div>
                </template>
            </div>
            <button class="console-operation__scroll console-operation__scroll--bottom" @click="scrollToBottom" v-show="!isScrolledBottom">
                <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>
            </button>
        </div>
    </component>
</template>

<script>
    import 'details-element-polyfill';
    import Loader from 'contao-package-list/src/components/fragments/Loader';

    export default {
        name: 'ConsoleOperation',
        components: { Loader },

        props: {
            status: String,
            summary: [String, Array],
            details: [String, Array],
            console: String,
            showConsole: Boolean,
            forceConsole: Boolean,
        },

        data: () => ({
            openConsole: true,
            isScrolledTop: true,
            isScrolledBottom: true,
            autoScroll: true,
            swallowScroll: true,
        }),

        computed: {
            isPending: vm => vm.status === 'pending',
            isActive: vm => vm.status === 'active',
            isSuccess: vm => vm.status === 'complete',
            isError: vm => vm.status === 'error',
            isStopped: vm => vm.status === 'stopped',
            isSkipped: vm => vm.status === 'skipped',

            consoleLines: vm => vm.console.trim().split('\n'),
        },

        methods: {
            toggleConsole() {
                const showConsole = this.$refs.details.open;

                if (showConsole && this.$refs.console) {
                    this.autoScroll = true;
                    this.$refs.console.scrollTop = this.$refs.console.scrollHeight;

                    this.updatePosition();
                }
            },

            scrolled() {
                if (!this.swallowScroll) {
                    this.updatePosition();
                }

                this.swallowScroll = false;
            },

            updatePosition() {
                const el = this.$refs.console;
                const height = (el.scrollTop + el.clientHeight);
                this.autoScroll = height === el.scrollHeight;
                this.isScrolledTop = el.clientHeight <= 250 || (el.scrollHeight > 250 && el.scrollTop < 16);
                this.isScrolledBottom = el.clientHeight <= 250 || (el.scrollHeight > 250 && height >= el.scrollHeight - 16);
            },

            scrollToTop() {
                this.$refs.console.scrollTop = 0;
            },

            scrollToBottom() {
                this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
            },

            updateConsole(value = true) {
                if (this.isError) {
                    value = true;
                }

                if (this.$refs.details) {
                    this.$refs.details.open = value;
                }
            },
        },

        watch: {
            console(value) {
                if (!value) {
                    return;
                }

                this.updateConsole(this.openConsole);

                if (this.autoScroll) {
                    this.$nextTick(() => {
                        this.swallowScroll = true;
                        this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                    });
                }
            },

            showConsole(value) {
                this.openConsole = value;
                this.updateConsole(value);
            },
        },

        mounted() {
            this.openConsole = this.showConsole;
            this.updateConsole(this.openConsole);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../../node_modules/contao-package-list/src/assets/styles/defaults";

    .console-operation {
        position: relative;
        padding: 0 16px;
        text-align: left;
        font-size: 12px;
        color: #959da5;

        &__summary {
            margin-left: 13px;
            padding: 8px;
            box-sizing: border-box;
            outline: none;

            &--console {
                margin-left: 0;
            }
        }

        summary {
            cursor: pointer;
        }

        &__status {
            display: inline-block;
            position: relative;
            box-sizing: border-box;
            padding-right: 8px;
            width: 24px;
            height: 18px;
            text-align: center;
            vertical-align: middle;
        }

        &__icon {
            position: absolute;
            left: 0;
            top: 0;

            &--skipped {
                fill: #666b71;
            }

            &--pending {
                fill: #dbab0a;
            }

            &--active {
                fill: #dbab0a;
                animation: console-active 1s linear infinite;

                @keyframes console-active {
                    0% {
                        transform: rotate(0deg);
                    }
                    50% {
                        transform: rotate(180deg);
                    }
                    100% {
                        transform: rotate(359deg);
                    }
                }
            }

            &--success {
                fill: $green-button;
            }

            &--error {
                fill: $red-button;
            }
        }

        &__label {
            display: inline-block;
            overflow: hidden;
            max-width: 750px;
            vertical-align: top;
        }

        &__title {
            display: inline;
            margin: 0;
            color: #fff;

            &--disabled {
                text-decoration: line-through;
            }
        }

        &__description {
            display: inline;
            margin: 0 0 0 10px;
        }

        &__console {
            position: relative;
        }

        &__lines {
            overflow-y: auto;
            max-height: 250px;
            padding: 8px 0 16px;
            font-family: SFMono-Regular,Consolas,Liberation Mono,Menlo,monospace;
            color: #f6f8fa;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        &__line {
            display: flex;

            &:hover {
                background-color: #2f363d;
            }
        }

        &__line-number {
            display: inline-block;
            overflow: hidden;
            width: 48px;
            min-width: 48px;
            color: #959da5;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
        }

        &__line-content {
            display: inline-block;
            margin-left: 16px;
            vertical-align: middle;
        }

        &__scroll {
            position: absolute;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            width: 100%;
            height: 30px;
            padding: 0;
            border: none;
            cursor: pointer;

            svg {
                fill: #fff;
                width: 16px;
                height: 16px;
            }

            &--top {
                top: 0;
                background: linear-gradient(#24292e, #24292e80 50%);

                svg {
                    transform: rotateZ(180deg);
                }
            }

            &--bottom {
                bottom: 0;
                background: linear-gradient(#24292e80, #24292e 50%);

                svg {
                    transform: rotateZ(0deg);
                }
            }
        }
    }
</style>
