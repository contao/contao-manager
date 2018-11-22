<template>
    <div class="widget widget-checkbox">
        <input
            ref="input"
            type="checkbox"
            :id="label ? 'ctrl_'+name : ''"
            :name="name"
            :disabled="disabled"
            :checked="value"
            @change="toggle($event.target.checked)"
        >
        <label v-if="label" :for="'ctrl_'+name">{{ label }}</label>
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
            },
            value: {
                type: Boolean,
            },
            disabled: {
                type: Boolean,
            },
        },
        methods: {
            toggle(value) {
                this.$emit('input', !!value);
            },
        },
    };
</script>
