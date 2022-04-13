<template>
    <div class="widget widget-radio-button">
        <div v-for="(option, k) in options" :key="k">
            <input
                ref="input"
                type="radio"
                :id="`ctrl_${name}_${option.value}`"
                :name="name"
                :disabled="option.disabled"
                :checked="value === option.value"
                @click="$emit('input', option.value)"
            >
            <label :for="`ctrl_${name}_${option.value}`" v-if="allowHtml" v-html="option.label"></label>
            <label :for="`ctrl_${name}_${option.value}`" v-else>{{ option.label }}</label>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String,
                required: true,
            },
            options: {
                type: Array,
                required: true,
            },
            value: {
                required: true,
            },
            disabled: {
                type: Boolean,
            },
            allowHtml: {
                type: Boolean,
                default: false,
            },
        },
    };
</script>

<style lang="scss">
    .widget-radio-button {
        > div {
            position: relative;
            margin: .25em 0;
        }

        input {
            position: absolute;
            visibility: hidden;
        }

        label {
            display: block;
            padding-left: 25px;
            background: url("../../assets/images/widget-radio--off.svg") 0 -1px no-repeat;
            background-size: 20px 20px;
        }

        input:checked + label {
            background-image: url("../../assets/images/widget-radio--on.svg");
        }

        input:disabled + label {
            opacity: .5;
        }
    }
</style>
