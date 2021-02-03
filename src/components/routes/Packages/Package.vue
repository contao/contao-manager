<template>
    <article class="package">

        <transition name="package__hint">
            <div class="package__hint" v-if="hint || !!$slots.hint">
                <slot name="hint">
                    <a href="#" class="package__hint-close" @click.prevent="$emit('close-hint')" v-if="hintClose">{{ hintClose }}</a>
                    <p>
                        {{ hint }}
                        <!--<a href="#">Help</a>-->
                    </p>
                </slot>
            </div>
        </transition>

        <div class="package__inside">
            <!--suppress HtmlUnknownTarget -->
            <package-logo class="package__icon" :src="logo"/>

            <div class="package__about">
                <h1 :class="{ package__headline: true, 'package__headline--badge': badge }">
                    <span class="package__title">{{ title }}</span>
                    <span class="package__badge" :title="badge.title" v-if="badge">{{ badge.text }}</span>
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

        <slot name="features"/>
    </article>
</template>

<script>
    import PackageLogo from 'contao-package-list/src/components/fragments/Logo';

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

    .package {
        margin-bottom: 14px;
        background: #fff;
        border-bottom: 3px solid #ddd3bc;
        border-radius: 2px;

        &--contao {
            border-bottom-color: $contao-color;
        }

        &__hint {
            position: relative;
            padding: 8px 20px 8px 20px;
            background: $hint-background;
            font-weight: $font-weight-medium;
            font-size: 12px;
            line-height: 1.8;
            border-radius: 2px 2px 0 0;

            @include screen(800) {
                padding-left: 56px;
                background: $hint-background url('~contao-package-list/src/assets/images/hint.svg') 20px 5px no-repeat;
                background-size: 28px 28px;
            }

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
            color: $hint-link;
            background: url('../../../assets/images/close.svg') left center no-repeat;
            background-size: 14px 14px;
        }

        &__inside {
            &:after {
                display: table;
                clear: both;
                content: "";
            }

            padding: 10px 20px 25px;

            @include screen(1024) {
                padding: 25px 20px;
            }
        }

        &__icon {
            display: none;

            img {
                width: 100%;
                height: 100%;
            }

            @include screen(1024) {
                display: block;
                float: left;
                width: 90px;
                height: 90px;
                margin-right: 20px;
            }
        }

        &__about {
            margin-bottom: 20px;

            @include screen(1024) {
                float: left;
                width: 370px;
                margin-bottom: 0;
            }

            @include screen(1200) {
                width: 590px;
            }
        }

        &__headline {
            position: relative;
            margin-bottom: 5px;

            em {
                background-color: $highlight-color;
                font-style: normal;
            }

            &--badge {
                padding-right: 100px;

                @include screen(1024) {
                    padding-right: 0;
                }
            }
        }

        &__title {
            margin-right: 10px;
        }

        &__badge {
            position: absolute;
            top: 6px;
            right: 0;
            padding: 0 8px;
            background: $red-button;
            border-radius: 2px;
            font-size: 12px;
            line-height: 19px;
            color: #fff;
            cursor: help;

            @include screen(800) {
                position: relative;
                display: inline-block;
                top: -3px;
                right: auto;
            }
        }

        &__description {
            display: -webkit-box;
            overflow: hidden;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 1em;

            em {
                background-color: $highlight-color;
                font-style: normal;
            }
        }

        &__additional {
            margin-top: -5px;
        }

        // Fixes CSS override with basic input styling
        .package__release {
            display: none;
            text-align: right;
            margin-bottom: 10px;

            @include screen(600) {
                display: block;
                float: left;
                width: 50%;
            }

            @include screen(1024) {
                width: 180px;
                margin-left: 40px;
                margin-bottom: 0;
            }
        }

        &__version {
            &--additional {
                strong {
                    margin-right: 10px;
                }

                @include screen(1024) {
                    display: none;
                }
            }

            &--release {
                display: none;

                @include screen(1024) {
                    display: block;
                    margin-top: 20px;
                    text-align: center;
                }

                time {
                    display: block;
                }
            }

            &--missing {
                padding: 4px 8px;
                background: $red-button;
                border-radius: 2px;
                color: #fff;
                font-weight: bold;
            }
        }

        &__version-update {
            display: inline-block;
            margin: 0 0 2px;
            padding: 1px 8px;
            color: #fff;

            &--available {
                background: $green-button;
            }

            &--error {
                background: $red-button;
            }

            &--none {
                background: $border-color;
            }

            @include screen(1024) {
                display: block;
                margin: 2px 0 0;
            }
        }

        &__actions {
            @include screen(600) {
                float: right;
                width: 50%;
                max-width: 500px;
                margin-top: -5px;
                padding-left: 40px;
                text-align: right;
            }

            @include screen(1024) {
                width: 180px;
                margin-left: 40px;
                padding-left: 0;
            }

            .widget-button:not(:last-child),
            .button-group:not(:last-child) {
                margin-bottom: 5px;
            }

            .button-group button {
                margin-bottom: 0 !important;
            }
        }

        &__features {
            padding: 0 0 10px 0;
            margin: -20px 0 0;

            @include screen(1024) {
                margin-top: -10px;
            }
        }
    }

    @include screen(960) {
        .package__hint {
            overflow: hidden;
            height: 37px;
            transition: height .4s ease;
        }
        .package__hint-enter, .package__hint-leave-to {
            height: 0;
        }
    }
</style>
