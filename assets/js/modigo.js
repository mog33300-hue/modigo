(function(){
"use strict";

function ready(fn){
    if(document.readyState==="loading"){
        document.addEventListener("DOMContentLoaded",fn);
    }else{
        fn();
    }
}

function toast(message,type="info"){
    if(!message)return;

    let box=document.querySelector(".modigo-toast-container");

    if(!box){
        box=document.createElement("div");
        box.className="modigo-toast-container";
        document.body.appendChild(box);
    }

    const item=document.createElement("div");
    item.className="modigo-toast modigo-toast-"+type;
    item.textContent=message;
    box.appendChild(item);

    setTimeout(()=>item.classList.add("is-visible"),20);

    setTimeout(()=>{
        item.classList.remove("is-visible");
        setTimeout(()=>item.remove(),250);
    },3500);
}

ready(function(){

    document.querySelectorAll("[data-confirm]").forEach(function(el){
        el.addEventListener("click",function(e){
            const msg=el.getAttribute("data-confirm")||"Confirmer cette action ?";
            if(!window.confirm(msg)){
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll("[data-modigo-back]").forEach(function(el){
        el.addEventListener("click",function(e){
            e.preventDefault();

            if(window.history.length>1){
                window.history.back();
            }else{
                window.location.href="dashboard.php";
            }
        });
    });

    document.querySelectorAll("form").forEach(function(form){
        form.addEventListener("submit",function(){
            form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(function(btn){
                if(btn.dataset.allowMultipleSubmit==="1")return;

                btn.disabled=true;
                btn.classList.add("is-loading");

                if(btn.tagName==="BUTTON"){
                    btn.dataset.originalText=btn.innerHTML;
                    btn.innerHTML="⏳ Enregistrement...";
                }
            });
        });
    });

    document.querySelectorAll(".stat,.stat-card,.status-card,.quick,.panel").forEach(function(el,index){
        el.style.animationDelay=(index*35)+"ms";
        el.classList.add("modigo-reveal");
    });
});

window.addEventListener("pageshow",function(){
    document.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(function(btn){
        btn.disabled=false;
        btn.classList.remove("is-loading");

        if(btn.tagName==="BUTTON"&&btn.dataset.originalText){
            btn.innerHTML=btn.dataset.originalText;
        }
    });
});


document.addEventListener("keydown",function(event){
    if(event.key==="Escape"){
        const back=document.querySelector("[data-modigo-back]");
        if(back){ back.click(); }
    }
});

window.Modigo={toast:toast};

})();