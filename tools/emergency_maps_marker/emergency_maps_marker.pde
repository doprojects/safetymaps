
size(100,100);

background(244);

PGraphics g2 = createGraphics(width,height,JAVA2D);
g2.smooth();

float w = width;
float h = height;

g2.stroke(#FF0013);
g2.strokeWeight(4);
g2.strokeJoin(MITER);
g2.strokeCap(SQUARE);

g2.noFill();

float s = 40;

g2.translate(w/2, h/2);

g2.pushMatrix();

g2.rotate(QUARTER_PI);

g2.beginShape();
g2.vertex(-s/2,-s/2+s/4);
g2.vertex(-s/2,-s/2);
g2.vertex(-s/2+s/4,-s/2);
g2.endShape();
g2.beginShape();
g2.vertex(s/2-s/4,-s/2);
g2.vertex(s/2,-s/2);
g2.vertex(s/2,-s/2+s/4);
g2.endShape();
g2.beginShape();
g2.vertex(s/2,s/2-s/4);
g2.vertex(s/2,s/2);
g2.vertex(s/2-s/4,s/2);
g2.endShape();
g2.beginShape();
g2.vertex(-s/2+s/4,s/2);
g2.vertex(-s/2,s/2);
g2.vertex(-s/2,s/2-s/4);
g2.endShape();

g2.popMatrix();

g2.beginShape();
g2.vertex(-s/3,0);
g2.vertex(s/3,0);
g2.endShape();

g2.beginShape();
g2.vertex(0,-s/3);
g2.vertex(0,s/3);
g2.endShape();

g2.save("cross.png");
