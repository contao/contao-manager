<template>
    <div class="popup-overlay">
        <div ref="popup" :class="popupClass">
            <h1 :class="headlineClass">
                {{ taskTitle }}
                <span class="task-popup__actions">
                    <button :class="'task-popup__autoclose task-popup__autoclose--' + (autoClose ? 'active' : '')" :title="$t('ui.taskpopup.autoclose')" v-if="allowAutoClose" @click="toggleAutoClose">
                        <svg fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                        </svg>
                    </button>
                    <button :class="'task-popup__toggle task-popup__toggle--' + (showConsole ? 'active' : '')" :title="$t('ui.taskpopup.console')" @click="toggleConsole" v-if="allowConsole">
                        <svg fill="#FFFFFF" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path fill="none" d="M0 0h24v24H0V0z"/><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                        </svg>
                    </button>
                </span>
            </h1>

            <div class="task-popup__status task-popup__status--complete" v-if="taskStatus === 'complete'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
            </div>
            <div class="task-popup__status task-popup__status--error" v-else-if="taskStatus === 'error' || taskStatus === 'stopped' || taskStatus === 'failed'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22C17.53,22 22,17.53 22,12C22,6.47 17.53,2 12,2M14.59,8L12,10.59L9.41,8L8,9.41L10.59,12L8,14.59L9.41,16L12,13.41L14.59,16L16,14.59L13.41,12L16,9.41L14.59,8Z" /></svg>
            </div>
            <div :class="statusClass" v-else>
                <div class="task-popup__progress task-popup__progress--20"></div>
                <div class="task-popup__progress task-popup__progress--40"></div>
                <div class="task-popup__progress task-popup__progress--60"></div>
                <div class="task-popup__progress task-popup__progress--80"></div>
                <div class="task-popup__progress task-popup__progress--100"></div>
                <p class="task-popup__progress-text" v-if="currentTask && currentTask.progress">{{ currentTask.progress }}%</p>
            </div>

            <div class="task-popup__summary" v-if="taskStatus === 'failed'">
                <h2 class="task-popup__text">{{ 'ui.taskpopup.failedHeadline' | translate }}</h2>
                <p class="task-popup__text" v-html="$t('ui.taskpopup.failedDescription')"></p>
                <p class="task-popup__text"><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.taskpopup.reportProblem' | translate }}</a></p>

                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonClose' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else>
                <h2 class="task-popup__text">{{ taskSummary }}</h2>
                <p class="task-popup__text">{{ taskDetail }}</p>

                <button class="widget-button" @click="cancelTask" v-if="isActive" :disabled="!currentTask || !currentTask.cancellable">{{ 'ui.taskpopup.buttonCancel' | translate }}</button>
                <button class="widget-button" v-if="isAborting" disabled>{{ 'ui.taskpopup.buttonAborting' | translate }}</button>
                <a class="widget-button widget-button--primary" href="/contao/install" @click="completeAudit" target="_blank" v-if="!isActive && requiresAudit">{{ 'ui.taskpopup.buttonAudit' | translate }}</a>
                <loading-button :loading="closing" @click="hidePopup" v-if="!isActive && !isAborting">
                    {{ $t('ui.taskpopup.buttonConfirm') }}
                </loading-button>
            </div>

            <div class="task-popup__console">
                <code ref="console" @scroll="scrolled" class="task-popup__output" v-show="showConsole && allowConsole"><i v-if="!taskConsole">{{ 'ui.taskpopup.noconsole' | translate }}</i>{{ taskConsole }}</code>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    import LoadingButton from '../widgets/LoadingButton';

    export default {
        components: { LoadingButton },

        data: () => ({
            showConsole: false,
            autoClose: false,
            scrollToBottom: true,
            swallowScroll: true,
            audit: false,

            closing: false,
        }),

        computed: {
            popupClass() {
                return {
                    'task-popup': true,
                    'task-popup--fixed': !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                    'task-popup--console': this.hasConsole && this.showConsole && this.taskStatus !== 'failed',
                };
            },

            headlineClass() {
                return {
                    'task-popup__headline': true,
                    'task-popup__headline--complete': (this.taskStatus === 'complete'),
                    'task-popup__headline--error': (this.taskStatus === 'error' || this.taskStatus === 'stopped' || this.taskStatus === 'failed'),
                };
            },

            statusClass() {
                return {
                    'task-popup__status': true,
                    'task-popup__status--active': true,
                };
            },

            taskTitle() {
                if (!this.currentTask || !this.currentTask.status) {
                    return this.$t('ui.taskpopup.taskLoading');
                }

                return this.currentTask.title;
            },

            taskSummary() {
                if (!this.currentTask || !this.currentTask.status) {
                    return this.$t('ui.taskpopup.taskLoading');
                }

                return this.currentTask.summary;
            },

            taskDetail() {
                if (!this.currentTask || !this.currentTask.status) {
                    return '';
                }

                return this.currentTask.detail;
            },

            allowAutoClose() {
                return this.currentTask && this.currentTask.status && this.currentTask.autoclose;
            },

            requiresAudit() {
                return this.audit
                    && this.currentTask
                    && this.currentTask.status === 'complete'
                    && this.currentTask.audit;
            },

            allowConsole() {
                return this.currentTask
                    && this.currentTask.status
                    && this.currentTask.console !== false;
            },

            hasConsole() {
                return this.currentTask && this.currentTask.console;
            },

            taskConsole() {
                if (!this.currentTask || !this.currentTask.status) {
                    return '';
                }

                return this.currentTask.console;
            },

            isActive() {
                return !this.currentTask || !this.currentTask.status || this.taskStatus === 'active';
            },

            isAborting() {
                return this.taskStatus === 'aborting';
            },

            ...mapState({
                taskStatus: state => state.tasks.status,
                currentTask: state => state.tasks.current,
            }),
        },

        methods: {
            toggleConsole() {
                this.showConsole = !this.showConsole;
                window.localStorage.setItem('contao_manager_console', this.showConsole ? '1' : '0');

                if (this.showConsole) {
                    this.scrollToBottom = true;
                    this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                }
            },

            toggleAutoClose() {
                this.autoClose = !this.autoClose;
                window.localStorage.setItem('contao_manager_autoclose', this.autoClose ? '1' : '0');
            },

            completeAudit() {
                this.audit = false;
            },

            hidePopup() {
                this.closing = true;
                const reload = this.taskStatus === 'stopped' || this.taskStatus === 'error';

                this.$store.dispatch('tasks/deleteCurrent').then(
                    () => {
                        this.closing = false;

                        if (reload) {
                            window.location.reload();
                        }
                    },
                );
            },

            cancelTask() {
                if (confirm(this.$t('ui.taskpopup.confirmCancel'))) {
                    this.$store.dispatch('tasks/abort');
                }
            },

            scrolled() {
                if (!this.swallowScroll) {
                    const el = this.$refs.console;
                    const height = (el.scrollTop + el.clientHeight);
                    this.scrollToBottom = height === el.scrollHeight;
                }

                this.swallowScroll = false;
            },
        },

        watch: {
            taskConsole() {
                if (this.scrollToBottom) {
                    this.$nextTick(
                        () => {
                            this.swallowScroll = true;
                            this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                        },
                    );
                }
            },
        },

        activated() {
            this.showConsole = window.localStorage.getItem('contao_manager_console') === '1';
            this.autoClose = window.localStorage.getItem('contao_manager_autoclose') === '1';
            this.scrollToBottom = true;
            this.audit = true;
        },

        deactivated() {
            this.$store.commit('tasks/setStatus', null);
            this.$store.commit('tasks/setCurrent', null);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .task-popup {
        position: fixed;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        width: 100%;
        height: 100%;
        text-align: center;
        background: #ffffff;
        z-index: 10;
        opacity: 1;

        > * {
            flex-basis: auto;
            flex-grow: 1;
        }

        &__headline {
            position: relative;
            background: $contao-color;
            color: #fff;
            font-weight: $font-weight-normal;
            line-height: 40px;
            border-radius: 2px 2px 0 0;

            &--complete {
                background-color: $green-button;
            }

            &--error {
                background-color: $red-button;
            }
        }

        &__actions {
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;

            svg {
                display: block;
                width: 22px;
                height: 22px;
            }
        }

        &__toggle,
        &__autoclose {
            display: block;
            float: right;
            margin: 4px 4px 4px 0;
            padding: 4px;
            background: none;
            border: 1px solid transparent;
            border-radius: 1px;
            cursor: pointer;

            &:hover,
            &--active {
                background-color: darken($contao-color, 5);
                border-color: darken($contao-color, 10);

                .task-popup__headline--complete & {
                    background-color: darken($green-button, 5);
                    border-color: darken($green-button, 10);
                }

                .task-popup__headline--error & {
                    background-color: $red-button;
                    border-color: darken($red-button, 10);
                }
            }
        }

        &__text {
            margin: 0 15px;
        }

        .widget-button {
            width: auto;
            height: 35px;
            margin: 2em 5px;
            padding: 0 30px;
            line-height: 35px;
        }

        &__console {
            flex-basis: 100vh;
            flex-grow: 1;
            position: relative;
        }

        &__output {
            position: absolute;
            display: block;
            overflow: auto;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 6px;
            font-size: 12px;
            text-align: left;
            color: #00ff00;
            white-space: pre;
            background: #181818;
        }

        &__status {
            &--complete,
            &--error {
                margin: 27px auto 20px;
                transform: scale(1.5);
            }

            &--complete svg {
                width: 40px;
                height: 40px;
                fill: $green-button;
            }

            &--error svg {
                width: 40px;
                height: 40px;
                fill: $red-button;
            }

            &--active {
                overflow: hidden;
                width: 165px;
                margin: 45px auto 20px;
                padding-left: 40px;
                text-align: center;

                > div {
                    opacity: .1;
                }
            }
        }

        &__progress {
            float: left;
            width: 16px;
            height: 16px;
            margin-right: 1px;
            background-color: $contao-color;
            animation: loading 1.4s infinite ease-in-out both;

            &--20 {
                animation-delay: -0.64s;
            }

            &--40 {
                animation-delay: -0.48s;
            }

            &--60 {
                animation-delay: -0.32s;
            }

            &--80 {
                animation-delay: -0.16s;
            }

            @keyframes loading {
                0%, 90%, 100% { opacity: 0; }
                20% { opacity: 1; }
            }
        }

        &__progress-text {
            float: left;
            width: 40px;
        }

        @include screen(960) {
            display: block;
            top: 50%;
            left: 50%;
            width: 750px;
            margin-left: -375px;
            height: auto;
            transform: translateY(-50%);
            border-bottom: 2px solid #ddd3bc;
            border-radius: 2px;

            &.fixed {
            }

            &__text {
                margin: 0 70px;
            }

            &__output {
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                height: 300px;
                margin: 0 70px 30px;
            }
        }
    }
</style>
