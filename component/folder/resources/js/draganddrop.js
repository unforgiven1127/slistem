/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function allowDrop(ev)
{
  ev.preventDefault();
}

function drag(ev)
{
  ev.dataTransfer.setData("Text",ev.target.id);
}

function drop(ev)
{
  ev.preventDefault();
  var data=ev.dataTransfer.getData("Text");
  ev.target.parent().child('ul.folder').append(document.getElementById(data));
  console.debug(ev.target);
  console.log(data);
  //ev.target.appendChild(document.getElementById(data));
}