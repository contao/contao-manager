<template>
    <boxed-layout :wide="true" slotClass="view-setup">
        <section class="view-setup__steps" v-if="currentStep > 0">
            <ul>
                <li :class="{ active: currentStep > i }" v-for="(step, i) in steps" :key="step.name">
                    <button @click="currentStep = i + 1" :disabled="currentStep <= i + 1">
                        <img :src="step.icon" width="24" height="24" alt="" />
                    </button>
                </li>
                <li :class="{ active: currentStep > steps.length }">
                    <button disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                    </button>
                </li>
            </ul>
        </section>

        <main class="view-setup__main" v-if="currentStep > steps.length">
            <span class="view-setup__party">🎉</span>
            <h1 class="view-setup__headline">{{ $t('ui.setup.complete') }}</h1>
            <p class="view-setup__description">{{ $t('ui.setup.complete1', { version: contaoVersion }) }}</p>

            <template v-if="showInstallTool">
                <p class="view-setup__description">{{ $t('ui.setup.complete2') }}</p>
                <button class="widget-button widget-button--inline" @click="launch">{{ $t('ui.setup.manager') }}</button>
                <a href="/contao/install" class="widget-button widget-button--primary view-setup__continue">{{ $t('ui.setup.installTool') }}</a>
            </template>
            <template v-else>
                <p class="view-setup__description">{{ $t('ui.setup.complete3') }}</p>
                <button class="widget-button view-setup__continue" @click="launch">{{ $t('ui.setup.manager') }}</button>
                <a href="/contao" class="widget-button widget-button--primary view-setup__continue">{{ $t('ui.setup.login') }}</a>
            </template>
            <div class="view-setup__funding">
                <figure><img src="~contao-package-list/src/assets/images/funding.svg" width="80" height="80" alt="" /></figure>
                <div>
                    <p v-for="(line, i) in $t('ui.setup.funding').split('\n')" :key="i">{{ line }}</p>
                    <p><a class="view-setup__funding-link widget-button widget-button--small widget-button--funding widget-button--link" href="https://to.contao.org/donate" target="_blank">{{ $t('ui.setup.fundingLink') }}</a></p>
                </div>
            </div>
        </main>

        <component :is="steps[currentStep - 1].component" @continue="currentStep += 1" v-else-if="currentStep > 0" />

        <main class="view-setup__main" v-else>
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="view-setup__icon" />
            <h1 class="view-setup__headline">{{ $t('ui.setup.welcome') }}</h1>
            <p class="view-setup__description">{{ $t('ui.setup.welcome1') }}</p>
            <i18n-t tag="p" class="view-setup__description" keypath="ui.setup.welcome2">
                <template #support><a href="https://to.contao.org/support" target="_blank">{{ $t('ui.setup.support') }}</a></template>
            </i18n-t>
            <button class="widget-button widget-button--inline widget-button--primary view-setup__start" @click="currentStep = 1">{{ $t('ui.setup.start') }}</button>
        </main>
    </boxed-layout>
</template>

<script>
import views from '../../router/views';
import { mapState } from 'vuex';

import BoxedLayout from '../layouts/BoxedLayout';
import DocumentRoot from '../setup/DocumentRoot';
import CreateProject from '../setup/CreateProject';
import DatabaseConnection from '../setup/DatabaseConnection';
import BackendUser from '../setup/BackendUser';

import DocumentRootIcon from '../../assets/images/document-root.svg';
import CreateProjectIcon from '../../assets/images/create-project.svg';
import DatabaseIcon from '../../assets/images/database.svg';
import UserIcon from '../../assets/images/user.svg';

export default {
    components: { BoxedLayout, DocumentRoot, CreateProject, DatabaseConnection, BackendUser },

    computed: {
        ...mapState(['setupStep']),
        ...mapState('server/contao', ['contaoVersion']),
        ...mapState('server/database', { databaseSupported: 'supported' }),
        ...mapState('server/adminUser', { userSupported: 'supported' }),
        ...mapState('contao/install-tool', { showInstallTool: 'isSupported' }),

        currentStep: {
            get() {
                return this.setupStep;
            },
            set(value) {
                this.$store.commit('setup', value);
            },
        },

        steps() {
            const steps = [];

            steps.push({
                name: 'document-root',
                icon: DocumentRootIcon,
                component: DocumentRoot,
            });

            steps.push({
                name: 'create-project',
                icon: CreateProjectIcon,
                component: CreateProject,
            });

            if (this.databaseSupported) {
                steps.push({
                    name: 'database-connection',
                    icon: DatabaseIcon,
                    component: DatabaseConnection,
                });
            }

            if (this.userSupported) {
                steps.push({
                    name: 'backend-user',
                    icon: UserIcon,
                    component: BackendUser,
                });
            }

            return steps;
        },
    },

    methods: {
        launch() {
            this.$store.commit('setView', views.READY);
        },
    },

    mounted() {
        this.$store.dispatch('server/adminUser/get');
        this.$store.dispatch('contao/backup/fetch');
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.view-setup {
    padding-top: 40px;

    footer {
        margin-top: 40px;
    }

    &__steps {
        padding: 0 0 50px 0;

        ul {
            display: flex;
            margin: 0;
            padding: 0;
            list-style-type: none;
        }

        li {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            position: relative;
            height: 6px;
            margin: 0;
            padding: 0;

            &:before {
                content: "";
                position: absolute;
                left: -50%;
                right: 50%;
                background: var(--border);
                height: 5px;
            }

            &:after {
                content: "";
                position: absolute;
                top: -18px;
                left: 50%;
                margin-left: -21px;
                width: 42px;
                height: 42px;
                color: #fff;
                text-indent: 0;
                text-align: center;
                line-height: 35px;
                background: var(--border);
                border-radius: 50%;
                z-index: 1;
            }
        }

        li:first-child:before {
            content: none;
        }

        li.active:before,
        li.active:after {
            background: var(--contao);
        }

        button {
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
            border: none;
            z-index: 10;
            cursor: pointer;

            &:disabled {
                cursor: default;
            }
        }

        svg {
            fill: #fff;
        }
    }

    &__main {
        text-align: center;
    }

    &__party {
        font-size: 64px;
    }

    &__headline {
        margin: 10px 0 20px;
        padding: 15px 0;
        font-size: 42px;
        font-weight: defaults.$font-weight-light;
    }

    &__description {
        max-width: 500px;
        margin: 1em 25px;
    }

    &__start.widget-button {
        height: 50px;
        margin: 30px 10px 0;
        padding: 0 50px;
        font-size: 1.2em;
        line-height: 50px;
    }

    &__continue.widget-button {
        width: 80% !important;
        margin: 10px 0 0;
    }

    &__funding {
        width: 80%;
        margin: 50px auto 0;
        padding: 20px 25px;
        border: 2px solid var(--funding);
        border-radius: var(--border-radius);
        background: rgba(var(--funding-rgb), 0.025);
        font-weight: 400;

        figure {
            margin-bottom: 1em;
        }

        p {
            margin: 0 0 0.5em 0;
        }

        &-link {
            margin: 1em 0 0;
        }
    }

    @include defaults.screen(960) {
        padding-top: 80px;

        footer {
            margin-top: 80px;
        }

        &__steps {
            padding-bottom: 80px;
        }

        &__headline {
            margin: 20px 0 40px;
            font-size: 64px;
        }

        &__continue.widget-button {
            width: auto !important;
            margin: 25px 10px 0;
            padding: 0 20px;
        }

        &__description {
            max-width: 550px;
            margin: 1em auto;
            font-size: 1.2em;
        }

        &__funding {
            display: flex;
            margin: 60px auto -20px;
            text-align: left;

            figure {
                margin-right: 25px;
            }
        }
    }
}

.setup {
    &__header {
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
        padding: 40px 0;
        text-align: center;

        .widget-button {
            margin-top: 1em;
        }
    }

    &__icon {
        background: var(--contao);
        border-radius: 10px;
        padding: 10px;
    }

    &__headline {
        margin-top: 20px;
        margin-bottom: 25px;
        font-size: 36px;
        font-weight: defaults.$font-weight-light;
        line-height: 1;
    }

    &__warning,
    &__description {
        margin: 1em 0;
        text-align: justify;
    }

    &__warning {
        color: var(--btn-alert);
        font-weight: defaults.$font-weight-bold;
    }

    &__form {
        position: relative;
        max-width: 280px;
        margin: 0 auto 50px;
        opacity: 1;

        svg.setup__check {
            display: block;
            width: 80px;
            height: 80px;
            margin: 0 auto 2em;
            fill: var(--btn-primary);
        }

        .widget-select,
        .widget-text {
            margin-top: 10px;

            label {
                display: block;
                margin-bottom: 5px;
                font-weight: defaults.$font-weight-medium;
            }
        }

        .widget-checkbox {
            margin-top: 20px;
            font-weight: defaults.$font-weight-medium;
        }

        .widget-radio-button {
            margin-top: 20px;
        }
    }

    &__fields {
        margin-bottom: 2em;

        &--center {
            text-align: center;
        }

        .button-group .widget-button {
            margin-bottom: 1px;
        }
    }

    &__fieldtitle {
        margin-bottom: 0.5em;
        font-size: 18px;
        font-weight: defaults.$font-weight-bold;
        line-height: 30px;
    }

    &__fielddesc {
        margin-bottom: 1em;
        text-align: left;

        code {
            word-break: break-word;
        }
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;

        &--center {
            justify-content: center;
        }

        .widget-button,
        .button-group {
            flex-grow: 1;

            &--inline {
                flex-grow: 0;
            }
        }
    }

    @include defaults.screen(960) {
        padding-top: 100px;

        &__header {
            float: left;
            width: 470px;
            max-width: none;
            padding: 0 60px 50px;
        }

        &__form {
            float: left;
            width: 370px;
            max-width: none;
            margin: 0 50px 50px;

            .widget-select,
            .widget-text {
                label {
                    display: block;
                    float: left;
                    width: 120px;
                    padding-top: 10px;
                    font-weight: defaults.$font-weight-medium;
                }

                select,
                input {
                    width: 250px !important;
                }
            }
        }
    }
}
</style>
