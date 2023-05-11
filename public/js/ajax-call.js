function addLike(id){
    var Route = Routing.generate('Likes');
    $.ajax({
        type: 'POST',
        url: Route,
        data: ({id: id}),
        async: true,
        dataType: "json",
        success: function (data) {
            window.location.reload();
        }
    });
}