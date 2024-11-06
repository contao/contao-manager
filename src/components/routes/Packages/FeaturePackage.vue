<template>
    <article class="feature-package" v-if="isRequired || isMissing || isRootInstalled || willBeInstalled">
        <p class="feature-package__text" :class="{ 'feature-package__text--hint': this.packageHint }">
            <strong class="feature-package__name">{{ packageTitle }}</strong>
            <span class="feature-package__hint" v-if="this.packageHint">{{ packageHint }}</span>
            <span class="feature-package__badge" :title="$t('ui.package.removedText')" v-else-if="isMissing">{{ $t('ui.package.removedTitle') }}</span>
            <span class="feature-package__badge" :title="$t('ui.package.requiredText')" v-else-if="isRequired">{{ $t('ui.package.requiredTitle') }}</span>
            <template v-else>{{ metadata.description }}</template>
        </p>

        <div class="feature-package__actions">
            <button class="feature-package__restore" @click="restore" v-if="packageHint && isGranted(scopes.INSTALL)">{{ $t('ui.package.hintRevert') }}</button>
            <details-button small :name="name"/>
            <button :title="$t('ui.package.removeButton')" class="widget-button widget-button--alert widget-button--trash widget-button--small" @click="uninstall" v-if="(isRequired || isRootInstalled) && !willBeRemoved && isGranted(scopes.INSTALL)"></button>
        </div>
    </article>
</template>

<script>
    import { mapGetters } from 'vuex';
    import scopes from '../../../scopes';
    import packageStatus from '../../../mixins/packageStatus';
    import DetailsButton from 'contao-package-list/src/components/fragments/DetailsButton';

    export default {
        mixins: [packageStatus],
        components: { DetailsButton },

        props: {
            name: String,
            reason: String,
        },

        computed: {
            ...mapGetters('auth', ['isGranted']),
            scopes: () => scopes,

            data: vm => ({ name: vm.name, }),

            packageTitle() {
                if (!this.metadata?.name) {
                    return this.data.name;
                }

                return this.metadata.title || this.metadata.name;
            },

            packageHint() {
                if (this.willBeRemoved) {
                    return this.$t('ui.package.hintRemoved');
                }

                if (this.willBeInstalled) {
                    return this.$t('ui.package.hintAdded');
                }

                return null;
            },
        },

        methods: {
            restore() {
                this.$store.commit('packages/restore', this.data.name);
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.feature-package {
    display: flex;
    flex-wrap: wrap;
    padding: 6px 16px;
    border-top: 1px solid var(--border--light);

    &__name {
        font-weight: defaults.$font-weight-bold;
        white-space: nowrap;

        &:after {
            content: ": ";
        }
    }

    &__text {
        flex-grow: 1;
        display: -webkit-box;
        overflow: hidden;
        -webkit-line-clamp: 1;
        line-clamp: 1;
        -webkit-box-orient: vertical;
        margin-right: .5em;
        padding: 4px 0;
        line-height: 20px;

        &--hint {
            display: inline;
            -webkit-line-clamp: none;
            line-clamp: none;
        }
    }

    &__badge {
        margin-left: 5px;
        padding: 2px 8px;
        background: var(--btn-alert);
        border-radius: var(--border-radius);
        font-size: 12px;
        font-weight: defaults.$font-weight-bold;
        line-height: 19px;
        color: #fff;
        cursor: help;
    }

    &__hint {
        line-height: 1.2;
        padding: 2px 5px;
        background: var(--hint-bg);
        font-size: 12px;
    }

    &__actions {
        flex-grow: 1;
        display: flex;
        justify-content: flex-end;
        margin: 0 -4px 0 0;

        > * {
            margin: 0 4px;
        }
    }

    &__restore {
        padding-left: 18px;
        font-size: 12px;
        color: var(--hint-link);
        background: url('../../../assets/images/close.svg') left center no-repeat;
        background-size: 14px 14px;
        border: none;
        outline: none;
        cursor: pointer;

        &:hover {
            text-decoration: underline;
        }
    }

    @include defaults.screen(800) {
        flex-wrap: nowrap;
    }

    @include defaults.screen(1024) {
        &__hint {
            padding: 8px 10px 8px 36px;
            background: var(--hint-bg) url('~contao-package-list/src/assets/images/hint.svg') 10px 5px no-repeat;
            background-size: 20px 20px;
        }

        &__actions {
            margin: 0 -4px 0 0;
        }
    }
}
</style>
