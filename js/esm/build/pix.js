import{useAsync as l}from"./translated";import{loadAmdModule as m}from"./amd";import{jsx as p}from"react/jsx-runtime";function a({name:n,component:e,title:o,className:r}){let t=l(null,()=>m("core/templates").then(({renderPix:s})=>s(n,e??"core",o)));return t?p("span",{className:r,dangerouslySetInnerHTML:{__html:t}}):null}export{a as default};
//# sourceMappingURL=pix.js.map
