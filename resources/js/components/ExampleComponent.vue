<template>
    <div>
        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>

        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>

        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>

        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>
        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>
        <h2>Example Vue Component</h2>
        <p>Message: {{ message }}</p>

        <button @click="changeMessage">Change message</button>
    </div>
</template>

<script>
export default {
    name: "ExampleComponent",

    data() {
        return {
            message: "Hello from Vue component",
        };
    },

    methods: {
        changeMessage() {
            this.message = "Message changed!";
        },
    },
};
</script>
