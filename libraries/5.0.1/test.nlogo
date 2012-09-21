globals [
  model-patches ; set of patches that are patch-based models
  max-rate ; per capita, used to set dt
  dt
  time-elapsed
  scaled-movement-rate
  scaled-relax-to-moving
  scaled-relax-to-stationary
]
;-----------------------------------------------------------
patches-own [ 
  patch-model ; true if patch-based model, false if turtle-based
  ; holes-model ; true if  hole-based model, false if neighbour-based
  ; for patch-based models
  mover-density 
  total-density
  ; rates (per capita = prob per individual on patch per unit time)
  pget-up-rate   ; per shaker on patch
  psit-down-rate ; per mover  on patch
  pmovew-rate    ; per mover  on patch, movement rate to West for hole-based models
  pmovee-rate    ; per mover  on patch, movement rate to East for hole-based models
]
;-----------------------------------------------------------
; for turtle-based models
breed [ movers  mover  ]
breed [ shakers shaker ] ; shakers don't move
; need to remember movew-rate/movee-rate regardless of breed so store all rates in all turtles
turtles-own [ get-up-rate sit-down-rate movew-rate movee-rate ]
;-----------------------------------------------------------
to neighbour-avoiding
  set holes-model false
  set stimulate-to-move   10
  set relax-to-stationary 10
  set stimulate-to-stop   0
  set relax-to-moving     1
  setup
  print "neighbour-avoiding"
end
;-----------------------------------------------------------
to neighbour-seeking
  set holes-model false
  set stimulate-to-stop   10
  set relax-to-moving     10
  set stimulate-to-move   0
  set relax-to-stationary 1
  setup
  print "neighbour-seeking"
end
;-----------------------------------------------------------
to hole-seeking
  set holes-model true
  set stimulate-to-stop   10
  set relax-to-moving     10
  set stimulate-to-move   0
  set relax-to-stationary 1
  setup
  print "hole-seeking"
end
;-----------------------------------------------------------
to hole-avoiding
  set holes-model true
  set stimulate-to-move   10
  set relax-to-stationary 10
  set stimulate-to-stop   0
  set relax-to-moving     1
  setup
  print "hole-avoiding"
end
;-----------------------------------------------------------
to diffusion
  set holes-model false
  set stimulate-to-move   0
  set relax-to-stationary 0
  set stimulate-to-stop   0
  set relax-to-moving     0
  setup
  print "diffusion"
end
;-----------------------------------------------------------
to setup
  clear-all
  print ""
  set-default-shape shakers "flower"
  set-default-shape movers "turtle"
  ; rescale order 1, single-reactant rates by carrying-capacity / 2 so they are comparable to order 2, pair rates
  let scale carrying-capacity * 0.5
  if not holes-model [ set scaled-movement-rate scale * movement-rate ]
  set scaled-relax-to-moving     scale * relax-to-moving
  set scaled-relax-to-stationary scale * relax-to-stationary
  ask patches [
    ; if agent-style=automatic then high-density models should be patch-based
    set patch-model ( agent-style = "patches" or ( agent-style = "automatic" and mean-density > auto-style-threshold ) )
    ; set holes-model ( pycor mod 2 = 0 )  ; alternate rows between neighbour-/hole-based
    let num-here random-binomial carrying-capacity ( mean-density / carrying-capacity)
    if-else patch-model [
      ; if patch-based model
      set mover-density num-here
      set total-density num-here
      ; only used in neighbour models, replaced in hole models
      set pmovew-rate scaled-movement-rate
      set pmovee-rate scaled-movement-rate
    ][
      ; if turtle-based model
      sprout-movers num-here [
        set heading 90 ; east
        forward random-float 0.8 - 0.4 ; spread out on patch for visibility
        ; only used in neighbour models, replaced in hole models
        set movew-rate scaled-movement-rate
        set movee-rate scaled-movement-rate
      ]
    ]
  ]
  set model-patches ( patches with [ patch-model = true ] )
  color-patches-and-turtles
  create-cv-pens
  reset-ticks
end
;-----------------------------------------------------------
to go
  reset-timer
  set-dt
  become-movers
  become-stoppers
  move
  color-patches-and-turtles
  tick-advance dt  
  every plot-interval [ plot-cv ]
  show-debug
  set time-elapsed time-elapsed + timer
end
;-----------------------------------------------------------
to set-dt
  set max-rate scaled-movement-rate   ; zero in hole models
  ; first 
  ; patch-based models
  ask model-patches [
    if-else holes-model [
      set pget-up-rate   and-set-max-rate ( scaled-relax-to-moving     + stimulate-to-move * holes )      ; S --> M and S + H --> M + H
      set psit-down-rate and-set-max-rate ( scaled-relax-to-stationary + stimulate-to-stop * holes )      ; M --> S and M + H --> S + H
      set pmovew-rate and-set-max-rate ( movement-rate * [ holes ] of patch-west )              ; M + H(west) --> M(west) + H
      set pmovee-rate and-set-max-rate ( movement-rate * [ holes ] of patch-east )              ; M + H(east) --> M(east) + H
    ][
      set pget-up-rate   and-set-max-rate ( scaled-relax-to-moving     + stimulate-to-move * ( density - 1 ) ) ; S --> M and S + N --> M + N
      set psit-down-rate and-set-max-rate ( scaled-relax-to-stationary + stimulate-to-stop * ( density - 1 ) ) ; M --> S and M + N --> S + N
      ; pmovew-rate and pmovee-rate already set in setup
    ]
  ]
  ; turtle-based models
  ask movers [
    if-else holes-model [
      set sit-down-rate and-set-max-rate ( scaled-relax-to-stationary + stimulate-to-stop * holes )       ; M --> S and M + H --> S + H
      set movew-rate and-set-max-rate ( movement-rate * [ holes ] of patch-west )               ; M + H(west) --> M(west) + H
      set movee-rate and-set-max-rate ( movement-rate * [ holes ] of patch-east )               ; M + H(east) --> M(east) + H
    ][
      set sit-down-rate and-set-max-rate ( scaled-relax-to-stationary + stimulate-to-stop * ( density - 1 ) ) ; M --> S and M + N --> S + N
      ; movew-rate and movee-rate already set in setup
    ]
  ]
  ask shakers [
    if-else holes-model [
      set get-up-rate and-set-max-rate ( scaled-relax-to-moving + stimulate-to-move * holes )             ; S --> M and S + H --> M + H
    ][
      set get-up-rate and-set-max-rate ( scaled-relax-to-moving + stimulate-to-move * ( density - 1 ) )   ; S --> M and S + N --> M + N
    ]
  ]
  ; max-rate for this iteration now known so can set dt to achieve desired error tolerance
  set dt  error-tolerance / max-rate
end
;-----------------------------------------------------------
to become-movers
  ; patch-based models
  ask model-patches [
    let stoppers total-density - mover-density
    let affected events-with-rate stoppers pget-up-rate
    set mover-density mover-density + affected
  ]
  ; turtle-based models
  ask shakers [
    if event-with-rate get-up-rate [ set breed movers ]
  ]
end
;-----------------------------------------------------------
to become-stoppers
  ; patch-based models
  ask model-patches [
    let affected events-with-rate mover-density psit-down-rate
    set mover-density mover-density - affected
  ]
  ; turtle-based models
  ask movers [
    if event-with-rate sit-down-rate [ set breed shakers ]
  ]
end
;-----------------------------------------------------------
to move
  ; patch-based models
  ask model-patches [
    move-to-patch patch-west events-with-rate mover-density pmovew-rate
    move-to-patch patch-east events-with-rate mover-density pmovee-rate
  ]
  ; turtle-based models
  ask movers [
    if event-with-rate movew-rate [ move-west ]
    if event-with-rate movee-rate [ move-east ]
  ]
end
;-----------------------------------------------------------
; reports intended mean density, as a function of pycor
to-report mean-density
  ; report carrying-capacity * ( int ( pycor / 2 ) + 1 ) / ( int ( max-pycor / 2 ) + 2 )
  report carrying-capacity * ( max-pycor - pycor + 1 ) / ( max-pycor - min-pycor + 2 )
end
;-----------------------------------------------------------
to color-patches-and-turtles
  ask model-patches [
    set pcolor hsb hue 128 ( 255 * total-density / carrying-capacity )
  ]
  ask turtles [
    ; set color hsb hue 128 ( 255 * ( count turtles-here ) / carrying-capacity )
    set color hsb hue 128 255
  ]
end
;-----------------------------------------------------------
to-report hue
  report 255 * mean-density / carrying-capacity
end
;-----------------------------------------------------------
; reports whether a single event occurs, given per capita rate
to-report event-with-rate [ this-rate ]
  let prob this-rate * dt
  report random-float 1 < prob
end

;-----------------------------------------------------------
; reports number of events to occur, given per capita rate and num-trials
to-report events-with-rate [ num-trials this-rate ]
  let prob this-rate * dt
  report random-binomial num-trials prob
end
;-----------------------------------------------------------
; Binomial random variate generator.
; Reports # successes in n trials with prob pp success for each
; http://groups.yahoo.com/group/netlogo-users/message/11866 .
; Slow algorithm.  Should use BTRS or BTPE instead.
to-report random-binomial [n pp]
  if ( pp > 1 ) [ set pp 1 error "Error: pp > 1" ]  ; error trap
  let p ifelse-value ( pp <= 0.5 ) [ pp ][ 1.0 - pp ]
  ; The binomial distribution is invariant under changing pp to 1-pp, if we also change the answer to 
  ; n minus itself; we'll remember to do this below.
  let am n * p
  let bnl 0
  ; trick if am small, from [Press92]
  ifelse ( am < 1.0 ) [
    set bnl random-poisson am
    if ( bnl > n ) [ set bnl n ]
  ][
    ifelse ( am > 5.0 ) [
      ; if n*p>5 then normal distribution good, mean=n*p, stdev = sqrt(n*p*(1-p))
      set bnl random-normal am sqrt ( am * ( 1.0 - p ) )
      ; error trap
      if bnl < 0 [ set bnl 0 error "Error: binomial: bnl < 0" ]
      if bnl > n [ set bnl n error "Error: binomial: bnl > n" ]
    ][
      ; count number of random-float 1's that are less than p
      set bnl length filter [? < p] n-values n [random-float 1]
    ]
  ]
  report ifelse-value (p != pp) [ n - bnl ][ bnl ]  ; Remember to undo the symmetry transformation.
end
;-----------------------------------------------------------
to-report error-tolerance
  report 10 ^ log-error-tol
end
;-----------------------------------------------------------
to-report cv [ row ]
; coefficient-of-variation of row's density.  Clustering estimate.
  let density-list [ density ] of patches with [ pycor = row ]
  report standard-deviation density-list / mean density-list
end
;-----------------------------------------------------------
to-report morisita-index [ row ]
; Morisita's index of row's density.  Clustering estimate.
; http://www.passagesoftware.net/webhelp/Dispersion_Indices.htm
  let density-list [ density ] of patches with [ pycor = row ]
  let n max-pxcor - min-pxcor + 1
  let xbar mean density-list
  let s2   variance density-list
  let id s2 / xbar
  report n * ( xbar + id ) / ( n * xbar - 1)
end
;-----------------------------------------------------------
to create-cv-pens
  create-temporary-plot-pen "zero"
  set-plot-pen-color black
  ask patches with [ pxcor = 0 ] [
    create-temporary-plot-pen (word pycor)
    set-current-plot-pen (word pycor)
    set-plot-pen-color approximate-hsb hue 128 255
    ; set-plot-pen-mode 2 ; points
  ]
end
;-----------------------------------------------------------
to plot-cv
  set-current-plot-pen "zero"
  plotxy ticks 0
  ask patches with [ pxcor = 0 ] [
    set-current-plot-pen (word pycor)
    plotxy ticks ln cv pycor
  ]
end
;-----------------------------------------------------------
to plot-morisita
  ask patches with [ pxcor = 0 ] [
    set-current-plot-pen (word pycor)
    plotxy ticks ln morisita-index pycor
  ]
end
;-----------------------------------------------------------
to clear
  clear-plot
  create-cv-pens
  let xmin precision ( ticks - dt ) ( - log dt 10 )
  let xmax ticks + dt
  set-plot-x-range xmin xmax
  
end
;-----------------------------------------------------------
to-report and-set-max-rate [ this-rate ]
  if this-rate > max-rate [ set max-rate this-rate ]
  report this-rate
end
;-----------------------------------------------------------
to show-debug
  ask model-patches [
    set plabel ifelse-value debug [ density ] [ "" ]
  ]
  ask turtles [
    set  label ifelse-value debug [ density ] [ "" ]
  ]
end
;-----------------------------------------------------------
to-report density
  report ifelse-value patch-model [ total-density ][ count turtles-here ]
end
;-----------------------------------------------------------
to-report holes
  let hole-count carrying-capacity - density
  report ifelse-value ( hole-count > 0 ) [ hole-count ][ 0 ]
end
;-----------------------------------------------------------
to-report patch-west
  report patch-at -1 0
end
;-----------------------------------------------------------
to-report patch-east
  report patch-at  1 0
end
;-----------------------------------------------------------
to move-west
  set heading 270 forward 1
end
;-----------------------------------------------------------
to move-east
  set heading  90 forward 1
end
;-----------------------------------------------------------
to move-to-patch [ destination-patch num-to-move ]
  set mover-density  mover-density - num-to-move
  set total-density  total-density - num-to-move
  ask destination-patch [ 
    set mover-density  mover-density + num-to-move
    set total-density  total-density + num-to-move
  ]
end
;-----------------------------------------------------------
@#$#@#$#@
GRAPHICS-WINDOW
8
10
818
121
-1
-1
16.0
1
10
1
1
1
0
1
1
1
0
49
0
4
0
0
1
ticks
30.0

SLIDER
278
174
450
207
stimulate-to-move
stimulate-to-move
0
10
0
1
1
NIL
HORIZONTAL

SLIDER
450
174
622
207
relax-to-stationary
relax-to-stationary
0
10
0
1
1
NIL
HORIZONTAL

SLIDER
278
207
450
240
stimulate-to-stop
stimulate-to-stop
0
10
0
1
1
NIL
HORIZONTAL

SLIDER
450
207
622
240
relax-to-moving
relax-to-moving
0
10
0
1
1
NIL
HORIZONTAL

BUTTON
131
388
252
421
custom
setup
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

SLIDER
363
132
535
165
movement-rate
movement-rate
0
10
10
1
1
NIL
HORIZONTAL

SLIDER
10
233
252
266
carrying-capacity
carrying-capacity
1
10
10
1
1
per patch
HORIZONTAL

BUTTON
100
435
163
468
NIL
go
T
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

SWITCH
10
265
252
298
holes-model
holes-model
0
1
-1000

MONITOR
638
266
818
315
NIL
ticks
10
1
12

SLIDER
638
133
818
166
log-error-tol
log-error-tol
-3
0
-1.3
0.1
1
NIL
HORIZONTAL

MONITOR
638
166
818
215
accuracy (%)
100 * ( 1 - error-tolerance )
2
1
12

BUTTON
11
356
132
389
NIL
neighbour-avoiding
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

BUTTON
11
324
132
357
NIL
neighbour-seeking
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

BUTTON
131
356
252
389
NIL
hole-seeking
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

BUTTON
131
324
252
357
NIL
hole-avoiding
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

PLOT
278
249
621
479
clustering
ticks
ln cv
0.0
0.0
0.0
0.0
true
false
"" ""
PENS
"default" 1.0 2 -16777216 true "" ""

BUTTON
11
388
132
421
NIL
diffusion
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

BUTTON
559
478
621
511
NIL
clear
NIL
1
T
OBSERVER
NIL
NIL
NIL
NIL
1

SLIDER
278
478
561
511
plot-interval
plot-interval
1
60
2
1
1
secs
HORIZONTAL

MONITOR
638
314
818
363
sim speed (ticks per sec)
ticks / time-elapsed
8
1
12

MONITOR
638
214
818
267
NIL
dt
8
1
13

CHOOSER
9
135
252
180
agent-style
agent-style
"automatic" "turtles" "patches"
0

SWITCH
77
478
180
511
debug
debug
1
1
-1000

TEXTBOX
14
308
164
326
setup
11
0.0
1

SLIDER
9
179
252
212
auto-style-threshold
auto-style-threshold
1
10
3.5
0.5
1
per patch
HORIZONTAL

@#$#@#$#@
## Rik's Notes

### 2012-07-27

The model looks good except I don't understand "hole-seeking".  I expected high dispersion (low clustering) but see the opposite.  Did I make a mistake?  If not, what's driving this behaviour?  (It reminds me of Alistair's stories of voles.)

It is probably just a mistake. Hole seeking leads to low clustering as expected in "patch-based" models, just not "turtle-based".  Check the code.

I see a patch with 11 turtles on it when the carrying-capacity is 4.  How?  Maybe too many turtles are moving on simultaneously and that causes blockages to form?

Found the bug: I had my turtles moving in the wrong direction (W vs E).

## WHAT IS IT?

(a general understanding of what the model is trying to show or explain)

## HOW IT WORKS

(what rules the agents use to create the overall behavior of the model)

## HOW TO USE IT

(how to use the model, including a description of each of the items in the Interface tab)

## THINGS TO NOTICE

(suggested things for the user to notice while running the model)

## THINGS TO TRY

(suggested things for the user to try to do (move sliders, switches, etc.) with the model)

## EXTENDING THE MODEL

(suggested things to add or change in the Code tab to make the model more complicated, detailed, accurate, etc.)

## NETLOGO FEATURES

(interesting or unusual features of NetLogo that the model uses, particularly in the Code tab; or where workarounds were needed for missing features)

## RELATED MODELS

(models in the NetLogo Models Library and elsewhere which are of related interest)

## CREDITS AND REFERENCES

(a reference to the model's URL on the web if it has one, as well as any other necessary credits, citations, and links)
@#$#@#$#@
default
true
0
Polygon -7500403 true true 150 5 40 250 150 205 260 250

airplane
true
0
Polygon -7500403 true true 150 0 135 15 120 60 120 105 15 165 15 195 120 180 135 240 105 270 120 285 150 270 180 285 210 270 165 240 180 180 285 195 285 165 180 105 180 60 165 15

arrow
true
0
Polygon -7500403 true true 150 0 0 150 105 150 105 293 195 293 195 150 300 150

box
false
0
Polygon -7500403 true true 150 285 285 225 285 75 150 135
Polygon -7500403 true true 150 135 15 75 150 15 285 75
Polygon -7500403 true true 15 75 15 225 150 285 150 135
Line -16777216 false 150 285 150 135
Line -16777216 false 150 135 15 75
Line -16777216 false 150 135 285 75

bug
true
0
Circle -7500403 true true 96 182 108
Circle -7500403 true true 110 127 80
Circle -7500403 true true 110 75 80
Line -7500403 true 150 100 80 30
Line -7500403 true 150 100 220 30

butterfly
true
0
Polygon -7500403 true true 150 165 209 199 225 225 225 255 195 270 165 255 150 240
Polygon -7500403 true true 150 165 89 198 75 225 75 255 105 270 135 255 150 240
Polygon -7500403 true true 139 148 100 105 55 90 25 90 10 105 10 135 25 180 40 195 85 194 139 163
Polygon -7500403 true true 162 150 200 105 245 90 275 90 290 105 290 135 275 180 260 195 215 195 162 165
Polygon -16777216 true false 150 255 135 225 120 150 135 120 150 105 165 120 180 150 165 225
Circle -16777216 true false 135 90 30
Line -16777216 false 150 105 195 60
Line -16777216 false 150 105 105 60

car
false
0
Polygon -7500403 true true 300 180 279 164 261 144 240 135 226 132 213 106 203 84 185 63 159 50 135 50 75 60 0 150 0 165 0 225 300 225 300 180
Circle -16777216 true false 180 180 90
Circle -16777216 true false 30 180 90
Polygon -16777216 true false 162 80 132 78 134 135 209 135 194 105 189 96 180 89
Circle -7500403 true true 47 195 58
Circle -7500403 true true 195 195 58

circle
false
0
Circle -7500403 true true 0 0 300

circle 2
false
0
Circle -7500403 true true 0 0 300
Circle -16777216 true false 30 30 240

cow
false
0
Polygon -7500403 true true 200 193 197 249 179 249 177 196 166 187 140 189 93 191 78 179 72 211 49 209 48 181 37 149 25 120 25 89 45 72 103 84 179 75 198 76 252 64 272 81 293 103 285 121 255 121 242 118 224 167
Polygon -7500403 true true 73 210 86 251 62 249 48 208
Polygon -7500403 true true 25 114 16 195 9 204 23 213 25 200 39 123

cylinder
false
0
Circle -7500403 true true 0 0 300

dot
false
0
Circle -7500403 true true 90 90 120

face happy
false
0
Circle -7500403 true true 8 8 285
Circle -16777216 true false 60 75 60
Circle -16777216 true false 180 75 60
Polygon -16777216 true false 150 255 90 239 62 213 47 191 67 179 90 203 109 218 150 225 192 218 210 203 227 181 251 194 236 217 212 240

face neutral
false
0
Circle -7500403 true true 8 7 285
Circle -16777216 true false 60 75 60
Circle -16777216 true false 180 75 60
Rectangle -16777216 true false 60 195 240 225

face sad
false
0
Circle -7500403 true true 8 8 285
Circle -16777216 true false 60 75 60
Circle -16777216 true false 180 75 60
Polygon -16777216 true false 150 168 90 184 62 210 47 232 67 244 90 220 109 205 150 198 192 205 210 220 227 242 251 229 236 206 212 183

fish
false
0
Polygon -1 true false 44 131 21 87 15 86 0 120 15 150 0 180 13 214 20 212 45 166
Polygon -1 true false 135 195 119 235 95 218 76 210 46 204 60 165
Polygon -1 true false 75 45 83 77 71 103 86 114 166 78 135 60
Polygon -7500403 true true 30 136 151 77 226 81 280 119 292 146 292 160 287 170 270 195 195 210 151 212 30 166
Circle -16777216 true false 215 106 30

flag
false
0
Rectangle -7500403 true true 60 15 75 300
Polygon -7500403 true true 90 150 270 90 90 30
Line -7500403 true 75 135 90 135
Line -7500403 true 75 45 90 45

flower
false
0
Polygon -10899396 true false 135 120 165 165 180 210 180 240 150 300 165 300 195 240 195 195 165 135
Circle -7500403 true true 85 132 38
Circle -7500403 true true 130 147 38
Circle -7500403 true true 192 85 38
Circle -7500403 true true 85 40 38
Circle -7500403 true true 177 40 38
Circle -7500403 true true 177 132 38
Circle -7500403 true true 70 85 38
Circle -7500403 true true 130 25 38
Circle -7500403 true true 96 51 108
Circle -16777216 true false 113 68 74
Polygon -10899396 true false 189 233 219 188 249 173 279 188 234 218
Polygon -10899396 true false 180 255 150 210 105 210 75 240 135 240

house
false
0
Rectangle -7500403 true true 45 120 255 285
Rectangle -16777216 true false 120 210 180 285
Polygon -7500403 true true 15 120 150 15 285 120
Line -16777216 false 30 120 270 120

leaf
false
0
Polygon -7500403 true true 150 210 135 195 120 210 60 210 30 195 60 180 60 165 15 135 30 120 15 105 40 104 45 90 60 90 90 105 105 120 120 120 105 60 120 60 135 30 150 15 165 30 180 60 195 60 180 120 195 120 210 105 240 90 255 90 263 104 285 105 270 120 285 135 240 165 240 180 270 195 240 210 180 210 165 195
Polygon -7500403 true true 135 195 135 240 120 255 105 255 105 285 135 285 165 240 165 195

line
true
0
Line -7500403 true 150 0 150 300

line half
true
0
Line -7500403 true 150 0 150 150

pentagon
false
0
Polygon -7500403 true true 150 15 15 120 60 285 240 285 285 120

person
false
0
Circle -7500403 true true 110 5 80
Polygon -7500403 true true 105 90 120 195 90 285 105 300 135 300 150 225 165 300 195 300 210 285 180 195 195 90
Rectangle -7500403 true true 127 79 172 94
Polygon -7500403 true true 195 90 240 150 225 180 165 105
Polygon -7500403 true true 105 90 60 150 75 180 135 105

plant
false
0
Rectangle -7500403 true true 135 90 165 300
Polygon -7500403 true true 135 255 90 210 45 195 75 255 135 285
Polygon -7500403 true true 165 255 210 210 255 195 225 255 165 285
Polygon -7500403 true true 135 180 90 135 45 120 75 180 135 210
Polygon -7500403 true true 165 180 165 210 225 180 255 120 210 135
Polygon -7500403 true true 135 105 90 60 45 45 75 105 135 135
Polygon -7500403 true true 165 105 165 135 225 105 255 45 210 60
Polygon -7500403 true true 135 90 120 45 150 15 180 45 165 90

sheep
false
0
Rectangle -7500403 true true 151 225 180 285
Rectangle -7500403 true true 47 225 75 285
Rectangle -7500403 true true 15 75 210 225
Circle -7500403 true true 135 75 150
Circle -16777216 true false 165 76 116

square
false
0
Rectangle -7500403 true true 30 30 270 270

square 2
false
0
Rectangle -7500403 true true 30 30 270 270
Rectangle -16777216 true false 60 60 240 240

star
false
0
Polygon -7500403 true true 151 1 185 108 298 108 207 175 242 282 151 216 59 282 94 175 3 108 116 108

target
false
0
Circle -7500403 true true 0 0 300
Circle -16777216 true false 30 30 240
Circle -7500403 true true 60 60 180
Circle -16777216 true false 90 90 120
Circle -7500403 true true 120 120 60

tree
false
0
Circle -7500403 true true 118 3 94
Rectangle -6459832 true false 120 195 180 300
Circle -7500403 true true 65 21 108
Circle -7500403 true true 116 41 127
Circle -7500403 true true 45 90 120
Circle -7500403 true true 104 74 152

triangle
false
0
Polygon -7500403 true true 150 30 15 255 285 255

triangle 2
false
0
Polygon -7500403 true true 150 30 15 255 285 255
Polygon -16777216 true false 151 99 225 223 75 224

truck
false
0
Rectangle -7500403 true true 4 45 195 187
Polygon -7500403 true true 296 193 296 150 259 134 244 104 208 104 207 194
Rectangle -1 true false 195 60 195 105
Polygon -16777216 true false 238 112 252 141 219 141 218 112
Circle -16777216 true false 234 174 42
Rectangle -7500403 true true 181 185 214 194
Circle -16777216 true false 144 174 42
Circle -16777216 true false 24 174 42
Circle -7500403 false true 24 174 42
Circle -7500403 false true 144 174 42
Circle -7500403 false true 234 174 42

turtle
true
0
Polygon -10899396 true false 215 204 240 233 246 254 228 266 215 252 193 210
Polygon -10899396 true false 195 90 225 75 245 75 260 89 269 108 261 124 240 105 225 105 210 105
Polygon -10899396 true false 105 90 75 75 55 75 40 89 31 108 39 124 60 105 75 105 90 105
Polygon -10899396 true false 132 85 134 64 107 51 108 17 150 2 192 18 192 52 169 65 172 87
Polygon -10899396 true false 85 204 60 233 54 254 72 266 85 252 107 210
Polygon -7500403 true true 119 75 179 75 209 101 224 135 220 225 175 261 128 261 81 224 74 135 88 99

wheel
false
0
Circle -7500403 true true 3 3 294
Circle -16777216 true false 30 30 240
Line -7500403 true 150 285 150 15
Line -7500403 true 15 150 285 150
Circle -7500403 true true 120 120 60
Line -7500403 true 216 40 79 269
Line -7500403 true 40 84 269 221
Line -7500403 true 40 216 269 79
Line -7500403 true 84 40 221 269

wolf
false
0
Polygon -7500403 true true 135 285 195 285 270 90 30 90 105 285
Polygon -7500403 true true 270 90 225 15 180 90
Polygon -7500403 true true 30 90 75 15 120 90
Circle -1 true false 183 138 24
Circle -1 true false 93 138 24

x
false
0
Polygon -7500403 true true 270 75 225 30 30 225 75 270
Polygon -7500403 true true 30 75 75 30 270 225 225 270

@#$#@#$#@
NetLogo 5.0.1
@#$#@#$#@
@#$#@#$#@
@#$#@#$#@
@#$#@#$#@
@#$#@#$#@
default
0.0
-0.2 0 1.0 0.0
0.0 1 1.0 0.0
0.2 0 1.0 0.0
link direction
true
0
Line -7500403 true 150 150 90 180
Line -7500403 true 150 150 210 180

@#$#@#$#@
0
@#$#@#$#@
