<template>
    <div>
        <div class="widget widget-checkbox" :class="{ 'widget--required': required }">
            <input
                ref="input"
                type="checkbox"
                :id="label ? 'ctrl_'+name : ''"
                :name="name"
                :disabled="disabled"
                :required="required"
                :checked="modelValue || null"
                @change="toggle($event.target.checked)"
            >
            <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
        </div>
        <div class="widget__description" :class="{ 'widget__description--disabled': disabled }" v-if="description || $slots.description">
            <slot name="description"><p>{{ description }}</p></slot>
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
            label: {
                type: String,
                required: true,
            },
            description: String,
            modelValue: Boolean,
            disabled: Boolean,
            required: Boolean,
        },
        methods: {
            toggle(value) {
                this.$emit('input');
                this.$emit('update:modelValue', !!value);
            },
        },
    };
</script>

<style lang="scss">
.widget-checkbox {
    input {
        position: absolute;
        visibility: hidden;
    }

    label {
        position: relative;
        display: block;
        padding-left: 25px;
        text-align: left;

        &:before {
            content: "";
            position: absolute;
            left: 0;
            width: 20px;
            height: 20px;
            background: url("../../assets/images/widget-checkbox--off.svg") 0 0 no-repeat;
            background-size: 20px 20px;
        }
    }

    input:checked + label {
        &:before {
            background-image: url("../../assets/images/widget-checkbox--on.svg");
        }
    }

    input:disabled + label {
        opacity: .5;
    }

    .widget__description {
        padding-left: 25px;

        &--disabled {
            opacity: .5;
        }
    }
}
</style>
