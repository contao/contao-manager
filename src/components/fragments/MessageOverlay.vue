<template>
    <div class="message-overlay">
        <div :class="{ 'message-overlay__blur': active }"><slot/></div>
        <div class="message-overlay__overlay" v-if="active">
            <span class="message-overlay__message">{{ message }}</span>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            message: {
                type: String,
                required: true,
            },
            active: {
                type: Boolean,
                required: true,
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .message-overlay {
        position: relative;

        &__blur {
            opacity: 0.75;
            filter: blur(2px);
            transition: opacity .5s, filter .5s;
        }

        &__overlay {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
        }

        &__message {
            padding: 10px;
            font-size: 2em;
            font-weight: $font-weight-medium;
            text-align: center;
        }
    }
</style>
