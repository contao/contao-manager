<template>
    <article class="package">

        <transition name="package__hint">
            <div class="package__hint" v-if="hint">
                <a href="#" class="error" @click.prevent="$emit('close-hint')" v-if="hintClose">{{ hintClose }}</a>
                <p>
                    {{ hint }}
                    <!--<a href="#">Help</a>-->
                </p>
            </div>
        </transition>

        <div class="package__inside">
            <figure class="package__icon">
                <slot name="logo">
                    <img :src="logo" v-if="logo">
                    <img src="../../../assets/images/placeholder.png" v-else>
                </slot>
            </figure>

            <div class="package__about">
                <h1 :class="{ package__headline: true, 'package__headline--badge': badge }">
                    <span class="package__title" v-html="title"></span>
                    <span class="package__name" v-if="name">{{ name }}</span>
                    <span class="package__badge" :title="badge.title" v-if="badge">{{ badge.text }}</span>
                </h1>

                <div class="package__description">
                    <p v-html="description"></p>
                    <slot name="more"/>
                </div>
                <p class="package__additional" v-if="!!$slots.additional">
                    <slot name="additional"/>
                </p>
            </div>

            <div :class="{package__release: true, 'package__release--validating': releaseValidating, 'package__release--error': releaseError, 'package__release--disabled': releaseDisabled }">
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
    </article>
</template>

<script>
    import Vue from 'vue';

    import More from './More';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { More, ButtonGroup },

        props: {
            title: String,
            name: String,
            logo: String,
            badge: Object,
            description: String,
            hint: String,
            hintClose: String,

            releaseValidating: Boolean,
            releaseError: Boolean,
            releaseDisabled: Boolean,
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../../assets/styles/defaults";

    .package {
        margin-bottom: 14px;
        background: #fff;
        border-bottom: 3px solid #ddd3bc;
        border-radius: 2px;

        &__hint {
            position: relative;
            padding: 8px 150px 8px 56px;
            font-weight: $font-weight-medium;
            font-size: 12px;
            line-height: 1.8;
            border-radius: 2px 2px 0 0;
            background: #e8c8bc url('../../../assets/images/hint.svg') 20px 5px no-repeat;
            background-size: 28px 28px;

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

            .error {
                position: absolute;
                right: 20px;
                top: 8px;
                padding-left: 18px;
                color: #bd2e20;
                background: url('../../../assets/images/close.svg') left center no-repeat;
                background-size: 14px 14px;
            }
        }

        &__inside {
            &:after {
                display: table;
                clear: both;
                content: "";
            }

            padding: 10px 20px 20px;

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
            padding-right: 10px;
        }

        &__name {
            position: relative;
            display: inline-block;
            top: -3px;
            padding: 0 8px;
            background: $border-color;
            border-radius: 2px;
            font-size: 12px;
            line-height: 19px;
            white-space: nowrap;
            color: #fff;

            em {
                background-color: $highlight-color;
                font-style: normal;
            }
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
            margin-bottom: 1em;

            p {
                display: inline;
            }

            em {
                background-color: $highlight-color;
                font-style: normal;
            }
        }

        &__additional {
            margin-top: -5px;

            *:not(:last-child):after {
                margin: 0 10px;
                font-weight: $font-weight-normal;
                content: "|";
            }
        }

        // Fixes CSS override with basic input styling
        .package__release {
            text-align: right;
            margin-bottom: 10px;

            @include screen(600) {
                float: left;
                width: 50%;
            }

            @include screen(1024) {
                width: 180px;
                margin-left: 40px;
                margin-bottom: 0;
            }

            input[type=text] {
                float: left;
                width: calc(100% - 32px);
                height: 30px;
                margin-right: 2px;
                background: #fff;
                border: 2px solid $orange-button;
                color: #000;
                font-weight: $font-weight-bold;
                text-align: center;
                line-height: 30px;

                &::placeholder {
                    color: #fff;
                    opacity: 1;
                }

                &:disabled {
                    color: #fff;
                    opacity: 1;
                    background: $orange-button;
                    -webkit-text-fill-color: #fff;
                }
            }

            button {
                width: 30px;
                height: 30px;
                padding: 6px;
                line-height: 20px;
                background: $orange-button url('../../../assets/images/settings.svg') center no-repeat;
                background-size: 20px 20px;
                text-indent: -999em;
            }

            /*&.validating button {
                animation: release-validating 2s linear infinite;
            }*/

            &--error input {
                animation: input-error .15s linear 3;
            }

            &--disabled {
                input[type=text],
                input[type=text]:disabled {
                    background: $border-color;
                    border-color: $border-color;

                    &::placeholder {
                        text-decoration: line-through;
                    }
                }
            }
        }

        &__version {
            &--additional {
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
        }

        &__proprietary {
            text-align: center;

            img {
                top: 6px;
                position: relative;

                @include screen(1024) {
                    display: block;
                    top: 0;
                    margin: 0 auto;
                }
            }
        }
    }

    /*@keyframes release-validating {
        100% {
            transform: rotate(360deg);
        }
    }*/

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
