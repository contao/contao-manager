<template>
    <article class="feature-package" v-if="isInstalled || willBeInstalled">
        <p class="feature-package__text" :class="{ 'feature-package__text--hint': this.packageHint }">
            <strong class="feature-package__name">{{ packageTitle }}</strong>
            <span class="feature-package__hint" v-if="this.packageHint">{{ packageHint }}</span>
            <template v-else>{{ reason }}</template>
        </p>

        <div class="feature-package__actions">
            <button class="feature-package__restore" @click="restore" v-if="packageHint">{{ $t('ui.package.hintRevert') }}</button>
            <button class="widget-button widget-button--alert widget-button--trash widget-button--small" @click="uninstall" v-if="(isRequired || isInstalled) && !willBeRemoved">{{ $t('ui.package.removeButton') }}</button>
            <details-button small :name="name"/>
        </div>
    </article>
</template>

<script>
    import metadata from 'contao-package-list/src/mixins/metadata';

    import DetailsButton from 'contao-package-list/src/components/fragments/DetailsButton';
    import packageStatus from '../../../mixins/packageStatus';

    export default {
        mixins: [packageStatus, metadata],
        components: { DetailsButton },

        props: {
            name: String,
            reason: String,
        },

        computed: {
            data: vm => ({ name: vm.name, }),

            packageTitle() {
                if (!this.metadata || !this.metadata.hasOwnProperty('name')) {
                    return this.data.name;
                }

                return this.metadata.title || this.metadata.name;
            },

            packageHint() {
                if (this.willBeRemoved) {
                    return this.$t('ui.package.hintRemoved');
                }

                if (this.isRequired || this.willBeInstalled) {
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
    @import "~contao-package-list/src/assets/styles/defaults";

    .feature-package {
        display: flex;
        flex-wrap: wrap;
        padding-top: 4px;
        margin: 4px 20px 4px;
        border-top: 1px solid #e9eef1;

        &:last-child {
            padding-bottom: 0;
            margin-bottom: -4px;
        }

        &__name {
            font-weight: $font-weight-bold;
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
            -webkit-box-orient: vertical;
            margin-right: .5em;
            padding: 4px 0;
            line-height: 20px;

            &--hint {
                display: inline;
                -webkit-line-clamp: none;
            }
        }

        &__hint {
            line-height: 1.2;
            padding: 2px 5px;
            background: $hint-background;
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
            color: $hint-link;
            background: url('../../../assets/images/close.svg') left center no-repeat;
            background-size: 14px 14px;
            border: none;
            outline: none;
            cursor: pointer;

            &:hover {
                text-decoration: underline;
            }
        }

        @include screen(800) {
            flex-wrap: nowrap;
        }

        @include screen(1024) {
            &__hint {
                padding: 8px 10px 8px 36px;
                background: #e8c8bc url('../../../assets/images/hint.svg') 10px 5px no-repeat;
                background-size: 20px 20px;
            }

            &__actions {
                margin: 0 -4px 0 0;
            }
        }
    }
</style>
