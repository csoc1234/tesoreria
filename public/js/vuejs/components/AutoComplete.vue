<!--
AUTOCOMPLETE COMPONENT

AUTOR: NELSON LOPEZ HIDALGO
VERSION: 0.2
FECHA: 11-09-2020
EMAIL: nelson.hidalgo1@gmail.com
-->
<template>
    <div>
        <input
            type="search"
            v-bind:name="customName"
            v-bind:placeholder="customPlaceHolder"
            class="form-control"
            @keyup="capturarTexto()"
            v-model="textoFiltro"
            autocomplete="off"
            :size="customSize"
            :id="customId"
            @blur="blurInput"
            :maxlength="maxLength"
            :time= customTime
            v-bind:disabled="customDisabled"
        />

        <div
            style="margin-top: 2px; height: 250px; overflow: auto"
            ref="idList"
            :id="idList"
            class="customDropdownDisplayNone"
        >
            <ul>
                <li
                    v-for="(item, index) in listadoItems"
                    :key="item[customKey]"
                    @mousedown="obtenerValorSeleccionado(item)"
                    :value="item[customKey]"
                >
                    {{ item[customValue] }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
module.exports = {
    name: "custom-autocomplete",

    data: function () {
        return {
            debounceTimer: 0,
            listadoItems: this.listData,
            idList: this.customIdList,
            itemKey: this.customKey,
            itemValue: this.customValue,
            textoFiltro: this.customText,
            maxLength: this.customMaxLength,
            size: this.customSize,
            time: this.customTime
            //   disabled1: this.customDisabled,
        };
    },
    watch: {
        listData: function (newVal, oldVal) {
            // watch it
            console.log("Prop changed: ", newVal, " | was: ", oldVal);

            if (newVal.length > 0) {
                this.listadoItems = newVal;
                this.mostrarListado();
            } else {
                this.textoFiltro = "";
                this.blurInput();
            }
        },
        customKey: function (newVal, oldVal) {
            // watch it
            console.log("Prop customKey changed: ", newVal, " | was: ", oldVal);
        },

        customText: function (newVal, oldVal) {
            // watch it
            console.log(
                "Prop customText changed: ",
                newVal,
                " | was: ",
                oldVal
            );

            this.textoFiltro = newVal;
        },
        customDisabled: function (newVal, oldVal) {
            // watch it
            console.log(
                "Prop customDisabled changed: ",
                newVal,
                " | was: ",
                oldVal
            );

            //  this.textoFiltro = newVal;
        },
    },
    props: {
        columns: {
            type: Number,
            default: 4,
        },
        listData: {
            type: Array,
            default: [],
        },

        customPlaceHolder: {
            type: String,
            default: "Ingrese una palabra clave",
        },
        customVmodel: {
            type: String,
            default: "",
        },
        customDisabled: {
            type: Boolean,
            default: false,
        },
        customName: {
            type: String,
            default: "filtro",
        },
        customSize: {
            type: Number,
            default: 25,
        },
        customIdList: {
            type: String,
            default: "",
        },
        customKey: {
            type: String,
            default: "",
        },
        customValue: {
            type: String,
            default: "",
        },
        customId: {
            type: String,
            default: "",
        },
        customText: {
            type: String,
            default: "",
        },
        customMaxLength: {
            type: Number,
            default: 145,
        },
        customTime: {
            type: String,
            default: 500,
        },
    },
    methods: {
        obtenerValorSeleccionado(item) {
            //console.log("por aqui....");
          //console.log(item);
            this.blurInput();
            this.textoFiltro = item[this.customValue];
            this.$emit("item-seleccionado", item);
        },

        capturarTexto() {
           // console.log({ textoFiltro: this.textoFiltro });
            if (this.textoFiltro.length === 0) {
                this.blurInput();
                return false;
            }
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.$emit("evento-filtro", this.textoFiltro);
            }, Number(this.time));
        },

        mostrarListado(event) {
            console.log({ mostrarListado: this.idList });
            console.log({ refs: this.$refs });
            console.log(this.idList);
            //this.$refs[this.idList].classList.value = "customDropdownDisplayBlock";
            const element = document.getElementById(this.idList);
            element.classList.remove("customDropdownDisplayNone");
            element.classList.add("customDropdownDisplayBlock");
        },

        blurInput(event) {
            console.log({ blurInput: event });
            const element = document.getElementById(this.idList);
            element.classList.remove("customDropdownDisplayBlock");
            element.classList.add("customDropdownDisplayNone");
        },
    },
    mounted() {
        console.log("Funciona mounted");
    },
    beforeUpdate() {
        console.log("Funciona beforeUpdate");
    },

    updated() {
        console.log("Funciona updated");
    },

    computed: {
        lstData: function () {
            // console.log({ lstData1: this.listadoItems });
            // console.log({ lstData1: this.lstData });
        },
    },
    created() {
        console.log({ key: this.itemKey });
        console.log({ value: this.itemValue });
        console.log({ idList: this.idList });
        console.log({ data: this.listadoItems });
        console.log({ customText: this.textoFiltro });
    },
};
</script>

<style scoped>
.customDropdownDisplayNone {
    display: none;
}

.customDropdownDisplayBlock {
    position: absolute;
    border: 1px solid #dcdcdc;
    z-index: 1;
    background: white;
    margin-top: -10px;
    display: block;
    width: 95.5%;
    cursor: pointer;
    height: auto;
    overflow-y: scroll;
}

.customDropdownDisplayBlock ul {
    list-style-type: none;
    margin-top: 10px;
    padding: 1%;
    margin-left: 1%;
}

.customDropdownDisplayBlock ul li {
    padding: 1%;
}

.customDropdownDisplayBlock ul li:hover {
    background-color: #f0f3f6;
}
</style>
