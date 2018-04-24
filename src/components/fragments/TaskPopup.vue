<template>
    <div class="popup-overlay">
        <div ref="popup" :class="popupClass">
            <h1 :class="{ 'task-popup__headline': true, 'task-popup__headline--complete': (taskStatus === 'success'), 'task-popup__headline--error': (taskStatus === 'error' || taskStatus === 'failed') }">{{ taskTitle }}</h1>

            <div class="task-popup__status task-popup__status--complete" v-if="taskStatus === 'success'"><i class="icono-checkCircle"></i></div>
            <div class="task-popup__status task-popup__status--error" v-else-if="taskStatus === 'error' || taskStatus === 'failed'"><i class="icono-crossCircle"></i></div>
            <div :class="statusClass" v-else>
                <div class="task-popup__progress task-popup__progress--20"></div>
                <div class="task-popup__progress task-popup__progress--40"></div>
                <div class="task-popup__progress task-popup__progress--60"></div>
                <div class="task-popup__progress task-popup__progress--80"></div>
                <div class="task-popup__progress task-popup__progress--100"></div>
                <p class="task-popup__progress-text" v-if="currentTask && currentTask.progress">{{ currentTask.progress }}%</p>
            </div>

            <div class="task-popup__summary" v-if="taskStatus === 'failed'">
                <h2 :class="textClass">{{ 'ui.taskpopup.failedHeadline' | translate }}</h2>
                <p :class="textClass" v-html="$t('ui.taskpopup.failedDescription')"></p>
                <p :class="textClass"><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.taskpopup.reportProblem' | translate }}</a></p>

                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonClose' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else-if="taskStatus === 'error'">
                <h2 :class="textClass">{{ 'ui.taskpopup.errorHeadline' | translate }}</h2>
                <p :class="textClass" v-html="$t('ui.taskpopup.errorDescription')"></p>

                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonConfirm' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else>
                <h2 :class="textClass">{{ taskSummary }}</h2>
                <p :class="textClass">{{ taskDetail }}</p>

                <button class="widget-button" @click="cancelTask" v-if="isActive" :disabled="!currentTask || !currentTask.cancellable">{{ 'ui.taskpopup.buttonCancel' | translate }}</button>
                <a class="widget-button widget-button--primary" href="/contao/install" @click="completeAudit" target="_blank" v-else-if="requiresAudit">{{ 'ui.taskpopup.buttonAudit' | translate }}</a>
                <button class="widget-button" @click="hidePopup" v-else>{{ 'ui.taskpopup.buttonConfirm' | translate }}</button>
            </div>

            <div v-if="hasConsole">
                <a @click.prevent="toggleConsole" :class="'task-popup__toggle task-popup__toggle--' + (showConsole ? 'hide' : 'show')">
                    <i class="icono-caretRight"></i>
                    <span v-if="showConsole">{{ 'ui.taskpopup.consoleHide' | translate }}</span>
                    <span v-else>{{ 'ui.taskpopup.consoleShow' | translate }}</span>
                </a>

                <code ref="console" @scroll="scrolled" :class="'task-popup__output' + (this.showConsole ? ' task-popup__output--visible' : '')">{{ taskConsole }}</code>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        data: () => ({
            showConsole: false,
            scrollToBottom: true,
            swallowScroll: true,
            audit: false,
        }),

        computed: {
            popupClass() {
                return {
                    'task-popup': true,
                    'task-popup--fixed': !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                    'task-popup--console': this.showConsole && this.taskStatus !== 'failed',
                };
            },

            textClass() {
                return {
                    'task-popup__text': true,
                    'task-popup__text--nowrap': this.taskStatus === 'active',
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

            requiresAudit() {
                return this.audit && this.currentTask && this.currentTask.status === 'complete' && this.currentTask.audit;
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

            completeAudit() {
                this.audit = false;
            },

            hidePopup() {
                const reload = this.taskStatus === 'failed' || this.taskStatus === 'error';

                this.$store.dispatch('tasks/deleteCurrent').then(
                    () => {
                        if (reload) {
                            window.location.reload();
                        }
                    },
                );
            },

            cancelTask() {
                if (confirm(this.$t('ui.taskpopup.confirmCancel'))) {
                    this.$store.dispatch('tasks/stop');
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

        activated() {
            this.showConsole = window.localStorage.getItem('contao_manager_console') === '1';
            this.scrollToBottom = true;
            this.audit = true;
        },

        deactivated() {
            this.$store.commit('tasks/setStatus', null);
            this.$store.commit('tasks/setProgress', null);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .task-popup {
        position: fixed;
        width: 100%;
        height: 100%;
        text-align: center;
        background: #ffffff;
        border-bottom: 2px solid #ddd3bc;
        border-radius: 2px;
        z-index: 10;
        opacity: 1;
        transition: opacity .5s linear;

        &__headline {
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

        &__text,
        &__toggle {
            margin: 0 15px;

            &--nowrap {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        .widget-button {
            width: auto;
            height: 35px;
            margin: 2em 5px;
            padding: 0 30px;
            line-height: 35px;
        }

        &__toggle {
            display: none;
        }

        &__output {
            display: block;
            overflow: scroll;
            position: absolute;
            top: 50%;
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

            &--complete i {
                color: $green-button;
            }

            &--error i {
                color: $red-button;
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
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100%;
            max-width: 750px;
            height: 310px;
            margin-left: -375px;
            margin-top: -155px;

            &.fixed {
            }

            &--console {
                height:630px;
                margin-top:-325px;
            }

            &__text,
            &__toggle {
                margin: 0 70px;
            }

            &__toggle {
                display: block;
                text-align: left;

                i {
                    position: relative;
                    top: -1px;
                    margin-right: 2px;
                    margin-left: 0;
                    color: $link-color;
                    transform: rotate(90deg) scale(0.5);
                }

                &--hide i {
                    transform: rotate(-90deg) scale(0.5);
                }
            }

            /*&.status-error a {
                visibility: hidden;
            }*/

            &__summary {
                height: 130px;
            }

            &__output {
                display: none;
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                margin: 15px 70px;
            }

            &--console code/*,
            &.status-error code*/ {
                display: block;
                height: 300px;
            }
        }
    }
</style>
