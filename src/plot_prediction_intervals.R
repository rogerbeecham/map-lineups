tmpGrid<-data_model  %>% filter(accuracy>0.55, geography=="1_grid")
predictionIntervalsGrid <- predictInterval(merMod = m.grid, newdata = tmpGrid, 
                                           level = 0.9, n.sims = 1000,
                                           stat = "median", type="linear.prediction",
                                           include.resid.var = TRUE)

tmpGrid<-cbind(tmpGrid, predictionIntervalsGrid)

#calculate fit line log transformed to base 2
coef = fixef(m.grid)
fit_slope = coef[2] / log(2)
fit_intercept = coef[1]/ log(2)

# create plot object with loess regression lines
gGrid <- ggplot(tmpGrid) + 
  stat_smooth(aes(x = base, y = lwr/log(2) ), color="#e41a1c", size=0.1,  method = "loess", se = FALSE) +
  stat_smooth( aes(x = base, y = upr/log(2)),  color="#e41a1c", size=0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg1 <- ggplot_build(gGrid)
# extract data for the loess lines 
df2 <- data.frame(x = gg1$data[[1]]$x,
                  ymin = gg1$data[[1]]$y,
                  ymax = gg1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
gGrid<-gGrid +
  geom_ribbon(data = df2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#e41a1c", alpha = 0.1)+
  scale_y_continuous(limits=c(-6, -0.1), labels=anti_log )+
  geom_abline(slope=fit_slope, intercept=fit_intercept, size=0.5, color= "#e41a1c", linetype="dashed" )+
  geom_point(data=tmpGrid,aes( x=base, y=log(jnd)/log(2)),size=3, color="#bdbdbd", alpha=.25)+
  labs(x="Moran's I", y="jnd")+
  ggtitle("regular grid")+
  theme_bw()


tmpReg<-data_model %>% filter(accuracy>0.55, geography=="2_reg")
predictionIntervalsReg <- predictInterval(merMod = m.reg, newdata = tmpReg, 
                                          level = 0.9, n.sims = 1000,
                                          stat = "median", type="linear.prediction",
                                          include.resid.var = TRUE)

tmpReg<-cbind(tmpReg, predictionIntervalsReg)

coef = fixef(m.reg)
fit_slope = coef[2] / log(2)
fit_intercept = coef[1]/ log(2)

# create plot object with loess regression lines
gReg <- ggplot(tmpReg) + 
  stat_smooth(aes(x = base, y = lwr/log(2) ), color="#377eb8", size=0.1,  method = "loess", se = FALSE) +
  stat_smooth( aes(x = base, y = upr/log(2)),  color="#377eb8", size=0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg1 <- ggplot_build(gReg)
# extract data for the loess lines from the 'data' slot
df2 <- data.frame(x = gg1$data[[1]]$x,
                  ymin = gg1$data[[1]]$y,
                  ymax = gg1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
gReg<-gReg +
  geom_ribbon(data = df2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#377eb8", alpha = 0.1)+
  scale_y_continuous(limits=c(-6, -0.1), labels=anti_log )+
  geom_abline(slope=fit_slope, intercept=fit_intercept, size=0.5, color= "#377eb8", linetype="dashed" )+
  geom_point(data=tmpReg,aes( x=base, y=log(jnd)/log(2)),size=3, color="#bdbdbd", alpha=.25)+
  labs(x="", y="")+
  ggtitle("regular real")+
  theme_bw()

tmpIrreg<-data_model %>% filter(accuracy>0.55, geography=="3_irreg")
predictionIntervalsIrreg <- predictInterval(merMod = m.irreg, newdata = tmpIrreg, 
                                            level = 0.95, n.sims = 1000,
                                            stat = "median", type="linear.prediction",
                                            include.resid.var = TRUE)

tmpIrreg<-cbind(tmpIrreg, predictionIntervalsIrreg)

tmp<-rbind(tmpGrid, tmpReg, tmpIrreg)

coef = fixef(m.irreg)
fit_slope = coef[2] / log(2)
fit_intercept = coef[1]/ log(2)

gIrreg <- ggplot(tmpIrreg) + 
  stat_smooth(aes(x = base, y = lwr/log(2) ), color="#4daf4a", size=0.1,  method = "loess", se = FALSE) +
  stat_smooth( aes(x = base, y = upr/log(2)),  color="#4daf4a", size=0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg1 <- ggplot_build(gIrreg)
# extract data for the loess lines from the 'data' slot
df2 <- data.frame(x = gg1$data[[1]]$x,
                  ymin = gg1$data[[1]]$y,
                  ymax = gg1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
gIrreg<-gIrreg +
  geom_ribbon(data = df2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#4daf4a", alpha = 0.1)+
  scale_y_continuous(limits=c(-6, -0.1), labels=anti_log )+
  geom_abline(slope=fit_slope, intercept=fit_intercept, size=0.5, color= "#4daf4a", linetype="dashed" )+
  geom_point(data=tmpIrreg,aes( x=base, y=log(jnd)/log(2)),size=3, color="#bdbdbd", alpha=.25)+
  labs(x="Moran's I", y="")+
  ggtitle("irregular real")+
  theme_bw()

grid.arrange(gGrid, gReg, gIrreg, ncol=3, nrow=1)

rm(tmpGrid, tmpReg, tmpIrreg)