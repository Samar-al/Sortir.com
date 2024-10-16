document.querySelectorAll(".delete-trip").forEach((element,key,array)=>{
    element.addEventListener("click",function (event) {

        const tripId = event.currentTarget.getAttribute("data-id");
        const tripCSRFToken = event.currentTarget.getAttribute("data-csrf-token");
        const tripNodeContainer = this.closest("[data-delete-trip]");

        if(!confirm("Voulez vous vraiment supprimer cette sortie!")) return false;

        if(tripId!==tripNodeContainer.getAttribute("data-delete-trip")) return;

        fetch("/trip/delete/"+tripId,{
            method:"POST",
            body: JSON.stringify({"_token":twittoCSRFToken})
        })
            .then(reponse=>reponse.json())
            .then(data=>{
                const json = JSON.parse(data);
                if(json.msg){
                    document.getElementById("msg").innerHTML= "<div class='alert'>"+json.msg+"</div>";
                }
                if(json.code===false) return;
                alert("ddd");
                twittoNodeContainer.remove();
            })
            .catch(error=>console.log(error))


    });
})