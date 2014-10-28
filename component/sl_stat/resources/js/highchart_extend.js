var edgeExtend = function(e)
{
  try
  {
    for (var l = this.series.length, i = 0; i< l; i++)
    {
      var series = this.series[i];
      var box = this.plotBox;
      var areaPath = series.areaPath;

      if(areaPath)
      {
        areaPath.splice(1, 0, 0, areaPath[2], "L");
        areaPath.splice(-6, 3, "L", box.width, areaPath[areaPath.length - 7], "L", box.width, box.height);
        areaPath[areaPath.length - 2] = 0;
        series.area.element.attributes.d.value = areaPath.join(" ");
      }

      var graphPath = series.graphPath;
      if(graphPath)
      {
        graphPath.splice(1, 0, 0, graphPath[2], "L");
        graphPath.push("L", box.width, graphPath[graphPath.length - 1]);

        series.graph.element.attributes.d.value = graphPath.join(" ");
      }
    }
  }
  catch(oEx)
  {}
};