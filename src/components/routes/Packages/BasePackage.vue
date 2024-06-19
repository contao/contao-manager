<template>
    <article class="package" :class="(hint || !!$slots.hint) ? 'is--hint' : ''">

        <div class="package__hint" v-if="hint || !!$slots.hint">
            <slot name="hint">
                <a href="#" class="package__hint-close" @click.prevent="$emit('close-hint')" v-if="hintClose">{{ hintClose }}</a>
                <p>{{ hint }}</p>
            </slot>
        </div>

        <div class="package__inside">
            <package-logo class="package__icon" :src="logo"/>

            <div class="package__details">

                <div class="package__about">
                    <h1 :class="{ package__headline: true, 'package__headline--badge': badge }">
                        <span class="package__badge" :title="badge.title" v-if="badge">{{ badge.text }}</span>
                        <span class="package__title">{{ title }}</span>
                    </h1>

                    <p class="package__description">{{ description }}</p>
                    <p class="package__additional">
                        <slot name="additional"/>
                    </p>
                </div>

                <div class="package__release">
                    <slot name="release">
                        <div></div>
                    </slot>
                </div>

                <fieldset class="package__actions">
                    <slot name="actions">
                        <div></div>
                    </slot>
                </fieldset>

            </div>

        </div>

        <slot name="features"/>
    </article>
</template>

<script>
    import PackageLogo from 'contao-package-list/src/components/fragments/PackageLogo';

    export default {
        components: { PackageLogo },

        props: {
            title: String,
            logo: String,
            badge: Object,
            description: String,
            hint: String,
            hintClose: String,
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    $package-padding: 16px;

    .package {
        position: relative;
        margin-bottom: 20px;
        background: var(--tiles-bg);
        border: 1px solid var(--tiles-bdr);
        border-radius: 14px;

        &.is--hint {
            border-color: var(--btn-alert);
        }

        &--contao {

            &:not(:last-child) {
                margin-bottom: 5em;
            }
        }

        &__hint {
            position: relative;
            background: var(--hint-bg);
            padding: 8px $package-padding;
            font-weight: $font-weight-medium;
            font-size: 12px;
            line-height: 1.8;
            border-radius: 14px 14px 0 0;
            z-index: 1;

            p a {
                display: inline-block;
                padding-right: 10px;

                &:first-child {
                    margin-left: 10px;
                }

                &:not(:first-child):before {
                    padding-right: 10px;
                    content: "|";
                }
            }
        }

        &__hint-close {
            float: right;
            padding-left: 18px;
            color: var(--hint-link);
            background: url('../../../assets/images/close.svg') left center no-repeat;
            background-size: 14px 14px;
        }

        &__inside {
            position: relative;
            padding: $package-padding;

            &:after {
                display: table;
                clear: both;
                content: "";
            }
        }

        &__badge {
            display: inline-block;
            margin-bottom: .5em;
            padding: 2px 5px;
            color: #fff;
            font-size: 12px;
            font-weight: $font-weight-bold;
            background: var(--btn-alert);
            border-radius: var(--border-radius);
            cursor: help;
        }

        &__icon {
            border-radius: 6px;
            height: 60px;
            width: 60px;
            background: #f7f7f7;
            margin: 0 auto 10px;
            position: absolute;
            right: $package-padding;

            > figure {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
            }

            img,
            svg {
                border-radius: 4px;
                width: 50px;
                height: 50px;
                max-height: 100%;
                object-fit: contain;
            }
        }

        &__details {
            //display: flex;
            //flex-direction: column;
            //justify-content: space-between;

            min-height: 96px;
        }

        &__about {
            margin-bottom: 20px;
        }

        &__headline {
            position: relative;
            margin-bottom: .2em;
            line-height: 1;
            overflow-wrap: break-word;
            margin-right: 70px;

            em {
                background-color: var(--highlight-bg);
                color: var(--highlight-color);
                font-style: normal;
            }
        }

        &__title {
            display: block;
            margin-right: 10px;
        }

        &__description {
            display: -webkit-box;
            overflow: hidden;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 1em;
            margin-right: 70px;

            em {
                background-color: var(--highlight-bg);
                color: var(--highlight-color);
                font-style: normal;
            }
        }

        &__additional {
            margin-top: -5px;
        }

        // Fixes CSS override with basic input styling
        .package__release {
            text-align: right;
            margin-bottom: 5px;
        }

        &__version {
            &--additional {
                margin-bottom: 5px;

                strong {
                    margin-right: 10px;
                }
            }

            &--release {
                display: none;

                time {
                    display: block;
                }
            }

            &--missing {
                padding: 4px 8px;
                background: var(--btn-alert);
                border-radius: var(--border-radius);
                color: #fff;
                font-weight: bold;
            }
        }

        &__version-update {
            display: inline-block;
            margin: 0 0 2px;
            padding: 1px 8px;
            color: #fff;
            border-radius: var(--border-radius);

            &--available {
                background: var(--btn-primary);
            }

            &--error {
                background: var(--btn-alert);
            }

            &--none {
                background: var(--border);
            }
        }

        &__version-latest {
            float: right;
            position: relative;
            right: -7px;
            width: 24px;
            height: 20px;
            background: var(--btn-primary) url('../../../assets/images/button-update.svg') center center/20px 20px no-repeat;
        }

        &__actions {
            display: flex;
            flex-flow: column;
            gap: 5px;

            .button-group button {
                margin-bottom: 0 !important;
            }
        }
    }

    @include screen(600) {
        .package {

            &__title,
            &__description {
                margin-right: 0;
            }

            &__hint {
                padding-left: 52px;
                background: rgba(var(--hint-rgb), 0.9) url('~contao-package-list/src/assets/images/hint.svg') 12px 5px no-repeat;
                background-size: 28px 28px;
            }

            &__inside {
                display: flex;
                align-items: stretch;
                padding: 0;
            }

            &__headline--badge {
                display: flex;
                gap: 6px;
                align-items: flex-start;
            }

            &__headline {
                margin: 0 0 8px;
            }

            &__badge {
                order: 1;
                margin: 0 0 0 2px;
            }

            &__icon {
                width: 130px;
                height: auto;
                min-height: 130px;
                margin: 0;
                border-radius: 12px 0 0 12px;

                position: revert;
                right: revert;

                img,
                svg {
                    width: 110px;
                    height: 110px;
                }

                .is--hint & {
                    border-top-left-radius: 0;
                }
            }

            &--contao {
                overflow: hidden;

                .package__icon {
                    border-radius: 0;
                }
            }

            &__details {
                padding: $package-padding;
                height: 100%;
                min-height: 90px;
                max-width: calc(100% - 130px);
                flex: 1;
            }

            &.is--hint {
                .package__icon {
                    border-top-left-radius: 0;
                }
            }
        }
    }

    @include screen(680) {
        .package {

            .package__release {
                display: block;
                float: left;
                width: 33%;
            }

            &__actions {
                float: right;
                width: 64%;
                flex-flow: row;
                gap: 4%;
                text-align: right;

                > * {
                    flex: 1;
                }
            }
        }
    }

    @include screen(1024) {
        .package {

            &__version {
                &--additional {
                    display: none;
                }

                &--release {
                    display: block;
                    margin-top: 15px;
                    text-align: center;
                }
            }

            &__version-update {
                display: block;
                margin: 2px 0 0;
            }

            &__about {
                float: left;
                width: 396px;
                margin-bottom: 0;
            }

            .package__release {
                width: 180px;
                margin-left: 20px;
                margin-bottom: 0;
            }

            &__actions {
                flex-flow: column;
                gap: 10px;
                width: 180px;
                margin-left: 20px;
            }
            &__details {
                display: flex;
                align-self: center;
                align-items: center;
            }
        }
    }

    @include screen(1200) {

        .package {
            &__about {
                width: 616px;
            }
        }
    }
</style>
