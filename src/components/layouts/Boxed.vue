<template>
    <div :class="'table' + (wide ? ' wide' : '')">
        <div class="cell">
            <main :class="mainClass">
                <slot name="header"></slot>
                <slot name="section"></slot>
                <div class="clear"></div>
                <footer-fragment></footer-fragment>
            </main>
        </div>
    </div>
</template>

<script>
    import FooterFragment from './../fragments/Footer';

    export default {
        components: { FooterFragment },
        props: ['mainClass', 'wide'],
    };
</script>
